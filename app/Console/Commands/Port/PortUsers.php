<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Enums\DiscountType;
use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Country;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\ExpertProfile;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\LegacyInvoiceAddress;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ports people and the seats they hold ([[01-schema]]). Runs after
 * `port:courses`, because every booking points at an event.
 *
 * Idempotent in the same way: clears its own tables and re-imports, so it can
 * be re-run against a fresher dump. Reconciliation is the point — every
 * mismatch it reports is a decision someone has to make before cutover.
 */
class PortUsers extends Command
{
	protected $signature = 'port:users {--dry-run : Report what would change without writing}';

	protected $description = 'Port users, addresses, discount codes and bookings from the legacy database';

	/**
	 * Uninvoiced bookings that have been looked at and deliberately left
	 * alone. Listed by number so a *new* one still shows up as a finding
	 * rather than being lost in a check somebody switched off.
	 *
	 * - 000309: free (fee 0.00), event 2024-07-08, two years past and never
	 *   billed. Accepted 2026-09-14 — too old to be worth reconstructing.
	 *
	 * @var array<int, string>
	 */
	private const ACCEPTED_UNINVOICED = ['000309'];

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, string> Known and decided; reported, but not as work. */
	private array $accepted = [];

	/**
	 * Things worth seeing but with nothing to decide — where the data is odd
	 * and the port already handles it the way the design says it should.
	 * Separate from findings so a real decision is never buried among them.
	 *
	 * @var array<int, string>
	 */
	private array $observations = [];

	public function handle(): int
	{
		$legacy = DB::connection('legacy');

		if (Event::count() === 0) {
			$this->components->error('No events in the target database. Run `port:courses` first — every booking points at one.');

			return self::FAILURE;
		}

		if ($this->option('dry-run')) {
			$this->reconcile($legacy);

			return self::SUCCESS;
		}

		// Unguarded because the port assigns `uuid`, which is deliberately not
		// fillable: it is the public route key and must never be settable from
		// a request. Here it has to carry across from legacy unchanged.
		Model::unguarded(function () use ($legacy): void {
			DB::transaction(function () use ($legacy): void {

				$this->clear();

				$this->portCountries($legacy);
				$userMap = $this->portUsers($legacy);
				$this->portAddresses($legacy, $userMap);
				$codeMap = $this->portDiscountCodes($legacy);
				$this->portBookings($legacy, $userMap, $codeMap);
			});
		});

		$this->reconcile($legacy);

		return self::SUCCESS;
	}

	/**
	 * Deletes rather than truncates, for the reason given in [[PortCourses]]:
	 * TRUNCATE commits implicitly and would leave a failed port half-applied.
	 *
	 * `users` is not cleared here — `port:courses` links experts to events
	 * through `event_expert`, so wiping users would take those links with it.
	 * Users are matched on email and updated in place instead.
	 */
	private function clear(): void
	{
		foreach (['bookings', 'discount_codes', 'user_addresses', 'expert_profiles'] as $table) {
			DB::table($table)->delete();
		}
	}

	private function portCountries($legacy): void
	{
		foreach ($legacy->table('countries')->whereNull('deleted_at')->get() as $row) {
			Country::updateOrCreate(
				['code' => mb_strtolower($row->iso)],
				[
					'name' => json_decode($row->name, true) ?: ['de' => $row->name],
					'order' => $row->order < 0 ? 99 : $row->order,
				],
			);
		}
	}

	/** @return array<int, int> legacy user id => new user id */
	private function portUsers($legacy): array
	{
		$map = [];
		$countries = Country::pluck('code', 'code');
		$legacyCountries = $legacy->table('countries')->pluck('iso', 'id');

		foreach ($legacy->table('users')->orderBy('id')->get() as $row) {
			$countryCode = mb_strtolower((string) ($legacyCountries[$row->country_id] ?? ''));

			if ($countryCode !== '' && ! isset($countries[$countryCode])) {
				$this->findings[] = "user {$row->id}: country_id {$row->country_id} is not in the countries table; address left without a country";
				$countryCode = null;
			}

			// Matched on email rather than created blind: `port:courses` has
			// already inserted the experts it found on events, and they must
			// keep the ids those `event_expert` rows point at.
			$user = User::withTrashed()->firstOrNew(['email' => $row->email]);

			$user->fill([
				'first_name' => $row->firstname,
				'last_name' => $row->name,
				'company' => $row->company,
				'street' => $row->street,
				'street_no' => $row->street_no,
				'zip' => $row->zip,
				'city' => $row->city,
				'country_code' => $countryCode ?: null,
				'phone' => $row->phone,
				'gender' => Gender::fromLegacyId((int) $row->gender_id),
				'operating_systems' => $this->operatingSystems($row->operating_system),
				'subscribe_newsletter' => (bool) $row->subscribe_newsletter,
			]);

			// Hashes come across as-is, so everyone's existing password keeps
			// working. Laravel rehashes on next login if the cost has changed.
			$user->forceFill([
				'uuid' => $row->uuid,
				'password' => $row->password,
				'email_verified_at' => $row->email_verified_at,
				'remember_token' => $row->remember_token,
				'created_at' => $row->created_at,
				'updated_at' => $row->updated_at,
				'deleted_at' => $row->deleted_at,
			])->save();

			$map[$row->id] = $user->id;

			$this->portRoles($legacy, $row->id, $user->id);
			$this->portExpertProfile($row, $user->id);
		}

		return $map;
	}

	private function portRoles($legacy, int $legacyUserId, int $userId): void
	{
		$roles = $legacy->table('role_user')
			->where('user_id', $legacyUserId)
			->pluck('role_id')
			->map(fn ($id) => Role::fromLegacyId((int) $id)->value)
			->unique();

		foreach ($roles as $role) {
			DB::table('role_user')->insertOrIgnore(['user_id' => $userId, 'role' => $role]);
		}
	}

	/**
	 * Only the 17 people who actually have a bio get a row. `publish` is true
	 * for 570 of 578 users in legacy, so it carries no information on its own
	 * — `visible` is what the Experten page really turns on.
	 */
	private function portExpertProfile(object $row, int $userId): void
	{
		if (blank($row->expert_title) && blank($row->expert_description)) {
			return;
		}

		ExpertProfile::create([
			'user_id' => $userId,
			'title' => $this->normaliseRichText($row->expert_title),
			'description' => $this->normaliseRichText($row->expert_description),
			'order' => $row->expert_order < 0 ? 999 : $row->expert_order,
			'publish' => (bool) $row->publish,
			'visible' => (bool) $row->visible,
		]);
	}

	/** @param array<int, int> $userMap */
	private function portAddresses($legacy, array $userMap): void
	{
		$legacyCountries = $legacy->table('countries')->pluck('iso', 'id');

		foreach ($legacy->table('user_addresses')->whereNull('deleted_at')->orderBy('id')->get() as $row) {
			if (! isset($userMap[$row->user_id])) {
				$this->findings[] = "user_address {$row->id}: user {$row->user_id} not found; skipped";

				continue;
			}

			UserAddress::create([
				'uuid' => $row->uuid,
				'user_id' => $userMap[$row->user_id],
				'first_name' => $row->firstname,
				'last_name' => $row->name,
				'company' => $row->company,
				'street' => $row->street,
				'street_no' => $row->street_no,
				'zip' => $row->zip,
				'city' => $row->city,
				'country_code' => mb_strtolower((string) ($legacyCountries[$row->country_id] ?? '')) ?: null,
			]);
		}
	}

	/** @return array<string, int> code => new discount_code id */
	private function portDiscountCodes($legacy): array
	{
		$map = [];

		// Soft-deleted codes come across too, still soft-deleted — the same
		// rule as soft-deleted events, and for the same reason. Booking 000640
		// spent a CHF 50 voucher that was deleted a month later; dropping the
		// row would leave the discount on the booking with nothing to say what
		// it was. 5 of the 107 codes are in this state.
		foreach ($legacy->table('discount_codes')->orderBy('id')->get() as $row) {
			$type = $this->discountType($row);

			if ($type === null) {
				$this->findings[] = "discount_code {$row->id} ({$row->code}): fix={$row->fix} percent={$row->percent} — neither or both; skipped";

				continue;
			}

			$map[$row->code] = DiscountCode::create([
				'uuid' => $row->uuid,
				'code' => $row->code,
				'type' => $type,
				'amount' => $row->amount,
				'valid_from' => $row->valid_from,
				'valid_to' => $row->valid_to,
				'remarks' => $row->remarks,
				'deleted_at' => $row->deleted_at,
			])->id;
		}

		return $map;
	}

	/**
	 * @param  array<int, int>  $userMap
	 * @param  array<string, int>  $codeMap
	 */
	private function portBookings($legacy, array $userMap, array $codeMap): void
	{
		$events = Event::withTrashed()->pluck('id', 'uuid');
		$legacyEvents = $legacy->table('events')->pluck('uuid', 'id');

		foreach ($legacy->table('bookings')->whereNull('deleted_at')->orderBy('id')->get() as $row) {
			$eventUuid = $legacyEvents[$row->event_id] ?? null;
			$eventId = $eventUuid === null ? null : ($events[$eventUuid] ?? null);

			// The events `port:courses` skipped take their bookings with them.
			// None of the 14 two-digit-year events has a booking, so this
			// should stay silent — if it ever speaks, the two ports disagree.
			if ($eventId === null) {
				$this->findings[] = "booking {$row->id} ({$row->number}): event {$row->event_id} was not ported; skipped";

				continue;
			}

			if (! isset($userMap[$row->user_id])) {
				$this->findings[] = "booking {$row->id} ({$row->number}): user {$row->user_id} not found; skipped";

				continue;
			}

			$codeId = null;

			if (filled($row->discount_code)) {
				$codeId = $codeMap[$row->discount_code] ?? null;

				if ($codeId === null) {
					$this->findings[] = "booking {$row->id} ({$row->number}): discount code {$row->discount_code} no longer exists; amount kept, link dropped";
				}
			}

			Booking::create([
				'uuid' => $row->uuid,
				'number' => $row->number,
				'event_id' => $eventId,
				'user_id' => $userMap[$row->user_id],
				'course_fee' => $row->course_fee ?? 0,
				'discount_code_id' => $codeId,
				'discount_amount' => $row->discount_amount ?? 0,
				'has_rental' => (bool) $row->has_rental,
				// Frozen from config, which is correct here for a reason that
				// will not hold forever: the rental has cost CHF 80 for the
				// whole life of the system — all 30 legacy rental invoices are
				// 80.00 — so there is no historical price to lose. From here
				// on the fee is captured at booking time ([[03-invoices]]).
				'rental_fee' => $row->has_rental ? config('invoice.rental_fee') : 0,
				'invoice_address' => LegacyInvoiceAddress::parse($row->invoice_address),
				'booked_at' => $row->booked_at,
				'cancelled_at' => $row->cancelled_at,
			]);
		}
	}

	/** Legacy stored the multi-select as a CSV of display labels. */
	private function operatingSystems(?string $value): ?array
	{
		if (blank($value)) {
			return null;
		}

		$systems = array_values(array_unique(array_filter(array_map(
			fn (string $label) => OperatingSystem::fromLegacyLabel($label)?->value,
			explode(',', $value),
		))));

		if ($systems === []) {
			$this->findings[] = "operating_system value '{$value}' matched no known system; dropped";
		}

		return $systems ?: null;
	}

	private function discountType(object $row): ?DiscountType
	{
		return match (true) {
			(bool) $row->fix && ! (bool) $row->percent => DiscountType::Fixed,
			(bool) $row->percent && ! (bool) $row->fix => DiscountType::Percent,
			default => null,
		};
	}

	private function normaliseRichText(?string $text): ?string
	{
		if (blank($text)) {
			return null;
		}

		return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}

	private function reconcile($legacy): void
	{
		$this->newLine();
		$this->components->info('Reconciliation');

		$rows = [];

		foreach ([
			'users' => [User::withTrashed()->count(), $legacy->table('users')->count()],
			'user_addresses' => [UserAddress::count(), $legacy->table('user_addresses')->whereNull('deleted_at')->count()],
			// Soft-deleted codes are ported rather than dropped, so both sides
			// count them.
			'discount_codes' => [DiscountCode::withTrashed()->count(), $legacy->table('discount_codes')->count()],
			'bookings' => [Booking::count(), $legacy->table('bookings')->whereNull('deleted_at')->count()],
		] as $table => [$after, $before]) {
			$skipped = $before - $after;
			$rows[] = [
				$table,
				$before,
				$after,
				match (true) {
					$skipped === 0 => 'ok',
					$skipped > 0 => $skipped.' skipped, see findings',
					default => 'MISMATCH',
				},
			];
		}

		$this->table(['table', 'legacy', 'ported', ''], $rows);

		$this->reportUninvoicedBookings($legacy);
		$this->reportFeeMismatches($legacy);

		if ($this->observations !== []) {
			$this->newLine();
			$this->components->info(count($this->observations).' observation(s) — the data is odd, the handling is settled:');

			foreach ($this->observations as $note) {
				$this->line('  - '.$note);
			}
		}

		if ($this->accepted !== []) {
			$this->newLine();
			$this->components->info(count($this->accepted).' known and accepted:');

			foreach ($this->accepted as $note) {
				$this->line('  - '.$note);
			}
		}

		if ($this->findings === []) {
			$this->components->info('No data-quality findings.');

			return;
		}

		$this->newLine();
		$this->components->warn(count($this->findings).' finding(s) needing a decision before cutover:');

		foreach ($this->findings as $finding) {
			$this->line('  - '.$finding);
		}
	}

	/**
	 * A live booking with no invoice is normal while the event is still ahead
	 * — the bill goes out closer to the date. One for an event that has
	 * already happened is not, and is worth a human look before cutover.
	 */
	private function reportUninvoicedBookings($legacy): void
	{
		$rows = $legacy->table('bookings as b')
			->leftJoin('invoices as i', function ($join): void {
				$join->on('i.booking_id', '=', 'b.id')->whereNull('i.deleted_at');
			})
			->join('events as e', 'e.id', '=', 'b.event_id')
			->whereNull('i.id')
			->whereNull('b.cancelled_at')
			->whereNull('b.deleted_at')
			->where('e.date', '<', now()->toDateString())
			->select('b.number', 'b.course_fee', 'e.date')
			->get();

		foreach ($rows as $row) {
			if (in_array($row->number, self::ACCEPTED_UNINVOICED, true)) {
				$this->accepted[] = "booking {$row->number}: never invoiced, event {$row->date} — accepted 2026-09-14, left as it is";

				continue;
			}

			$this->findings[] = "booking {$row->number}: event was {$row->date} and it was never invoiced (fee {$row->course_fee})";
		}
	}

	/**
	 * Six bookings were invoiced at exactly half their course fee under an
	 * arrangement recorded nowhere but the invoice. Reported, not corrected:
	 * the invoice is what the customer paid and what the books show.
	 *
	 * An observation rather than a finding — the design already answers it,
	 * and nobody has to decide anything before cutover.
	 */
	private function reportFeeMismatches($legacy): void
	{
		$rows = $legacy->table('bookings as b')
			->join('invoices as i', function ($join): void {
				$join->on('i.booking_id', '=', 'b.id')->where('i.is_rental', 0)->whereNull('i.deleted_at');
			})
			->whereNull('b.deleted_at')
			->whereRaw('ABS(b.course_fee - i.total) > 0.01')
			->select('b.number', 'b.course_fee', 'i.number as invoice_number', 'i.total')
			->get();

		foreach ($rows as $row) {
			$this->observations[] = sprintf(
				'booking %s: course_fee %s but invoice %s charged %s — the invoice is the money, booking left as-is',
				$row->number,
				$row->course_fee,
				$row->invoice_number,
				$row->total,
			);
		}
	}
}
