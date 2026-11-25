<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Models\Country;
use App\Models\LicenceOrder;
use App\Models\LicenceOrderItem;
use App\Models\LicenceVariant;
use App\Models\User;
use App\Support\LegacyTime;
use App\Support\LicenceOrderNumber;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Brings 3d-software.ch's orders over as licence orders (`05-licences.md`,
 * *The old shop's orders*). Runs at cutover after `port:users`, against a
 * dump of the shop's Shopware 6 database taken that day (`.rework/Deploy.md`).
 *
 * - **Accounts are merged by email** (Marcel, 2026-11-25): a shop customer
 *   with a VIAK account gets the orders on it, untouched; everyone else gets a
 *   new account from their newest billing address, with no mail. Shopware
 *   hashes with bcrypt as Laravel does, so a registered customer's shop
 *   password carries over and works; a guest has none and uses *Passwort
 *   vergessen*. The address counts as verified: licences went to it.
 * - **The shop's order number is kept**, padded to six digits (10006 becomes
 *   010006), so a customer quoting it finds it, and new orders continue after
 *   the highest ([[LicenceOrderNumber]]).
 * - **No invoice is raised**: the shop raised its own. The order carries the
 *   shop's total and the customer's note instead.
 * - **The shop's states were not kept up**: hundreds of orders from 2020-2022
 *   are still *open*. Orders older than three months count as sent, and an
 *   open payment older than twelve months as paid (Marcel, 2026-11-25:
 *   "ignore those, but make a list"). Both are measured back from the newest
 *   order in the dump, not today, so one dump always gives one result. Every
 *   order where the import assumed something, or that stays open, is in the
 *   CSV the run writes for VIAK.
 *
 * Rerunnable: it deletes the orders it imported before and imports again.
 * Accounts it made stay and are matched by email the next time.
 */
class PortShop extends Command
{
	protected $signature = 'port:shop
		{--dry-run : Report what would change without writing}
		{--report= : Where to write the CSV of assumed and open orders}';

	protected $description = 'Port orders and customers from the 3d-software.ch Shopware database';

	/** Shopware's live version (`Defaults::LIVE_VERSION`); other versions are drafts of an edit. */
	public const LIVE_VERSION = '0fa91ce3e96a4bc2be4bd9ce752c3425';

	public const SENT_AFTER_MONTHS = 3;

	public const PAID_AFTER_MONTHS = 12;

	private const SHIPPED = ['shipped', 'shipped_partially', 'returned', 'returned_partially'];

	private const PAID = ['paid', 'paid_partially'];

	/** @var array<int, string> */
	private array $findings = [];

	/** @var array<int, array<int, string>> */
	private array $report = [];

	/** @var array<string, int> */
	private array $counts = ['orders' => 0, 'lines' => 0, 'linked' => 0, 'matched' => 0, 'created' => 0, 'passwords' => 0, 'outstanding' => 0];

	/** @var array<string, int> article numbers sold that are not in the catalogue, with how often */
	private array $unknownSkus = [];

	public function handle(): int
	{
		$shop = DB::connection('shop');
		$live = hex2bin(self::LIVE_VERSION);

		$orders = collect($shop->table('order')->where('version_id', $live)->orderBy('order_date_time')->get());

		if ($orders->isEmpty()) {
			$this->components->error('No orders in the shop database. Is SHOP_DB_* pointing at the dump?');

			return self::FAILURE;
		}

		$data = $this->read($shop, $live);

		$numbers = app(LicenceOrderNumber::class);
		$clash = LicenceOrder::query()->whereNull('shop_order_id')
			->whereIn('number', $orders->map(fn ($order) => $numbers->pad((int) $order->order_number))->all())
			->pluck('number');

		if ($clash->isNotEmpty()) {
			$this->components->error('Orders entered here already hold shop numbers: '.$clash->implode(', ').'. Run on the database before VIAK takes orders.');

			return self::FAILURE;
		}

		$newest = Carbon::parse($orders->max('order_date_time'), 'UTC');
		$sentBefore = $newest->copy()->subMonths(self::SENT_AFTER_MONTHS);
		$paidBefore = $newest->copy()->subMonths(self::PAID_AFTER_MONTHS);

		DB::beginTransaction();

		Model::unguarded(fn () => Model::withoutEvents(function () use ($orders, $data, $numbers, $sentBefore, $paidBefore): void {
			LicenceOrder::query()->whereNotNull('shop_order_id')->delete();

			$users = $this->accounts($orders, $data);

			foreach ($orders as $order) {
				$this->order($order, $data, $users, $numbers, $sentBefore, $paidBefore);
			}
		}));

		$this->option('dry-run') ? DB::rollBack() : DB::commit();

		$path = $this->writeReport();
		$this->summary($newest, $sentBefore, $paidBefore, $path);

		return self::SUCCESS;
	}

	/**
	 * Everything but the orders, keyed for lookup, binary ids as hex.
	 *
	 * @return array<string, mixed>
	 */
	private function read(Connection $shop, string $live): array
	{
		$byOrder = fn (string $table) => collect($shop->table($table)->where('version_id', $live)->get())
			->keyBy(fn ($row) => bin2hex($row->order_id));

		// History rows name their entity as JSON: {"id": hex, "version_id": hex}.
		$history = collect($shop->table('state_machine_history')->orderBy('created_at')->get())
			->groupBy(fn ($row) => (json_decode((string) $row->entity_id, true)['id'] ?? '').'|'.bin2hex($row->to_state_id))
			->map(fn (Collection $rows) => $rows->last()->created_at);

		return [
			'states' => collect($shop->table('state_machine_state')->get())->mapWithKeys(fn ($row) => [bin2hex($row->id) => $row->technical_name]),
			'countries' => collect($shop->table('country')->get())->mapWithKeys(fn ($row) => [bin2hex($row->id) => mb_strtolower((string) $row->iso)]),
			'customers' => $byOrder('order_customer'),
			'addresses' => collect($shop->table('order_address')->where('version_id', $live)->get())->keyBy(fn ($row) => bin2hex($row->id)),
			'transactions' => $byOrder('order_transaction'),
			'deliveries' => $byOrder('order_delivery'),
			'lines' => collect($shop->table('order_line_item')->where('version_id', $live)->orderBy('position')->get())
				->groupBy(fn ($row) => bin2hex($row->order_id)),
			'accounts' => collect($shop->table('customer')->get())->keyBy(fn ($row) => bin2hex($row->id)),
			'history' => $history,
			'known' => Country::pluck('code')->flip(),
			'variants' => LicenceVariant::withTrashed()->pluck('id', 'sku')->mapWithKeys(fn ($id, $sku) => [mb_strtoupper((string) $sku) => $id]),
		];
	}

	/**
	 * One account per email, lower-cased: the VIAK account where there is one,
	 * a new one from the newest order's billing address where there is not.
	 *
	 * @return array<string, int> email => user id
	 */
	private function accounts(Collection $orders, array $data): array
	{
		$users = [];
		// A password nobody knows, hashed once: bcrypt per guest took two minutes.
		$unknown = Hash::make(Str::random(40));

		foreach ($orders->groupBy(fn ($order) => $this->email($order, $data)) as $email => $mine) {
			if ($email === '') {
				continue;
			}

			$existing = User::withTrashed()->where('email', $email)->first();

			if ($existing) {
				$users[$email] = $existing->id;
				$this->counts['matched']++;

				if ($existing->trashed()) {
					$this->findings[] = "{$email}: matches a deleted VIAK account; its shop orders go on it";
				}

				continue;
			}

			$newest = $mine->last();
			$customer = $data['customers'][bin2hex($newest->id)];
			$address = $data['addresses'][bin2hex($newest->billing_address_id)] ?? null;
			[$street, $number] = self::splitStreet((string) ($address->street ?? ''));
			$country = $address ? ($data['countries'][bin2hex((string) $address->country_id)] ?? null) : null;

			// The shop account with a password, the one used last, if the
			// customer registered at all; several rows per email are guests.
			$password = $mine->map(fn ($order) => $data['customers'][bin2hex($order->id)]->customer_id ?? null)
				->filter()
				->map(fn ($id) => $data['accounts'][bin2hex($id)] ?? null)
				->filter(fn ($account) => $account && filled($account->password))
				->sortBy(fn ($account) => $account->last_login ?? $account->updated_at ?? $account->created_at)
				->last()?->password;

			$user = new User;
			$user->forceFill([
				'uuid' => (string) Str::uuid(),
				'email' => trim((string) $customer->email),
				'first_name' => $address->first_name ?? $customer->first_name,
				'last_name' => $address->last_name ?? $customer->last_name,
				'company' => filled($address->company ?? null) ? $address->company : (filled($customer->company) ? $customer->company : null),
				'street' => $street,
				'street_no' => $number,
				'zip' => $address->zipcode ?? null,
				'city' => $address->city ?? null,
				'country_code' => $country !== null && isset($data['known'][$country]) ? $country : null,
				'phone' => filled($address->phone_number ?? null) ? $address->phone_number : null,
				'password' => $password ?? $unknown,
				'email_verified_at' => LegacyTime::local($mine->first()->order_date_time),
				'created_at' => LegacyTime::local($mine->first()->order_date_time),
				'updated_at' => now(),
			])->save();

			$users[$email] = $user->id;
			$this->counts['created']++;
			$this->counts['passwords'] += $password ? 1 : 0;
		}

		return $users;
	}

	private function order(object $order, array $data, array $users, LicenceOrderNumber $numbers, Carbon $sentBefore, Carbon $paidBefore): void
	{
		$id = bin2hex($order->id);
		$email = $this->email($order, $data);

		if (! isset($users[$email])) {
			$this->findings[] = "order {$order->order_number}: no customer email, skipped";

			return;
		}

		$state = fn (?object $row) => $row ? ($data['states'][bin2hex($row->state_id)] ?? null) : null;
		$when = fn (?object $row) => $row ? $data['history']->get(bin2hex($row->id).'|'.bin2hex($row->state_id)) : null;

		$orderState = $state($order);
		$transaction = $data['transactions'][$id] ?? null;
		$delivery = $data['deliveries'][$id] ?? null;
		$payState = $state($transaction);
		$sendState = $state($delivery);
		$placed = Carbon::parse($order->order_date_time, 'UTC');
		$total = number_format((float) $order->amount_total, 2, '.', '');
		$notes = [];

		$cancelled = $orderState === 'cancelled' ? ($when($order) ?? $order->updated_at ?? $order->order_date_time) : null;

		// When the lines went out: the shop's shipped or completed date, or
		// the order date for an old order whose state nobody moved on.
		$dispatched = null;
		if (! $cancelled) {
			if (in_array($sendState, self::SHIPPED, true)) {
				$dispatched = $when($delivery) ?? $order->order_date_time;
				if ($sendState !== 'shipped') {
					$notes[] = "Versand im Shop «{$sendState}», als versendet übernommen";
				}
			} elseif ($orderState === 'completed') {
				$dispatched = $when($order) ?? $order->order_date_time;
			} elseif ($placed->lt($sentBefore)) {
				$dispatched = $order->order_date_time;
				$notes[] = 'als versendet angenommen';
			} else {
				$notes[] = 'Versand offen, steht in den offenen Bestellungen';
				$this->counts['outstanding']++;
			}
		}

		$paid = null;
		if ($cancelled) {
			if (in_array($payState, self::PAID, true)) {
				$notes[] = 'storniert, im Shop bezahlt: Rückzahlung prüfen';
			}
		} elseif (in_array($payState, self::PAID, true)) {
			$paid = $when($transaction) ?? $order->order_date_time;
			if ($payState !== 'paid') {
				$notes[] = "Zahlung im Shop «{$payState}», als bezahlt übernommen";
			}
		} elseif (bccomp($total, '0', 2) <= 0) {
			$paid = $order->order_date_time;
		} elseif ($placed->lt($paidBefore)) {
			$paid = $order->order_date_time;
			$notes[] = 'als bezahlt angenommen';
		} else {
			$notes[] = 'Zahlung offen übernommen';
		}

		$address = $data['addresses'][bin2hex($order->billing_address_id)] ?? null;
		[$street, $number] = self::splitStreet((string) ($address->street ?? ''));
		$country = $address ? ($data['countries'][bin2hex((string) $address->country_id)] ?? null) : null;

		$licenceOrder = new LicenceOrder;
		$licenceOrder->forceFill([
			'uuid' => (string) Str::uuid(),
			'number' => $numbers->pad((int) $order->order_number),
			'shop_order_id' => $id,
			'user_id' => $users[$email],
			'invoice_address' => $address ? [
				'first_name' => $address->first_name,
				'last_name' => $address->last_name,
				'company' => filled($address->company) ? $address->company : null,
				'street' => $street,
				'street_no' => $number,
				'zip' => $address->zipcode,
				'city' => $address->city,
				'country_code' => $country,
			] : null,
			'total' => $total,
			'comment' => filled($order->customer_comment) ? trim((string) $order->customer_comment) : null,
			'paid_at' => LegacyTime::local($paid),
			'cancelled_at' => LegacyTime::local($cancelled),
			'created_at' => LegacyTime::local($order->order_date_time),
			'updated_at' => LegacyTime::local($order->updated_at ?? $order->order_date_time),
		])->save();

		$this->counts['orders']++;

		foreach (($data['lines'][$id] ?? collect())->values() as $index => $line) {
			$payload = json_decode((string) $line->payload, true) ?: [];
			$sku = filled($payload['productNumber'] ?? null) ? mb_strtoupper(str_replace('_', '-', trim((string) $payload['productNumber']))) : null;
			$variant = $sku !== null ? ($data['variants'][$sku] ?? null) : null;

			if ($sku !== null && $variant === null) {
				$this->unknownSkus[$sku] = ($this->unknownSkus[$sku] ?? 0) + 1;
			}

			$item = new LicenceOrderItem;
			$item->forceFill([
				'uuid' => (string) Str::uuid(),
				'licence_order_id' => $licenceOrder->id,
				'licence_variant_id' => $variant,
				'title' => Str::limit(trim((string) $line->label), 250, ''),
				'sku' => $sku !== null ? Str::limit($sku, 32, '') : null,
				'price' => number_format((float) $line->unit_price, 2, '.', ''),
				'quantity' => max(1, (int) $line->quantity),
				'position' => $index + 1,
				'dispatched_at' => LegacyTime::local($dispatched),
				'created_at' => $licenceOrder->created_at,
				'updated_at' => $licenceOrder->created_at,
			])->save();

			$this->counts['lines']++;
			$this->counts['linked'] += $variant ? 1 : 0;
		}

		if ($notes) {
			$customer = $data['customers'][$id];
			$this->report[] = [
				$licenceOrder->number,
				$placed->copy()->setTimezone(config('app.timezone'))->format('d.m.Y'),
				trim("{$customer->first_name} {$customer->last_name}").(filled($customer->company) ? ", {$customer->company}" : ''),
				$email,
				$total,
				(string) $orderState,
				(string) $payState,
				(string) $sendState,
				implode('; ', $notes),
			];
		}
	}

	private function email(object $order, array $data): string
	{
		return mb_strtolower(trim((string) ($data['customers'][bin2hex($order->id)]->email ?? '')));
	}

	/**
	 * Shopware keeps street and number in one field, the site in two: the
	 * number at the end ("Hardturmstrasse 66", "Bahnhofstr. 12a") or, as in
	 * French addresses, at the start ("68 Rue du Vieux-Pont"). Anything else
	 * stays whole in the street.
	 *
	 * @return array{0: string|null, 1: string|null}
	 */
	public static function splitStreet(string $street): array
	{
		$street = trim(preg_replace('/\s+/', ' ', $street) ?? '');

		if ($street === '') {
			return [null, null];
		}

		if (preg_match('/^(.*\D)\s+(\d+\s?[a-zA-Z]?(?:\s?[-\/]\s?\d+\s?[a-zA-Z]?)?)$/u', $street, $match)) {
			return [trim($match[1], ' ,'), $match[2]];
		}

		if (preg_match('/^(\d+\s?[a-zA-Z]?),?\s+(\D.*)$/u', $street, $match)) {
			return [$match[2], $match[1]];
		}

		return [$street, null];
	}

	/** Semicolons and a byte-order mark, so a double click opens it in Excel with umlauts intact. */
	private function writeReport(): string
	{
		$path = $this->option('report') ?: storage_path('app/private/port/shop-orders.csv');
		File::ensureDirectoryExists(dirname($path));

		$handle = fopen($path, 'w');
		fwrite($handle, "\u{FEFF}");
		fputcsv($handle, ['Bestellung', 'Datum', 'Kunde', 'E-Mail', 'Total CHF', 'Shop: Bestellung', 'Shop: Zahlung', 'Shop: Versand', 'Übernommen als'], ';');

		foreach (array_reverse($this->report) as $row) {
			fputcsv($handle, $row, ';');
		}

		fclose($handle);

		return $path;
	}

	private function summary(Carbon $newest, Carbon $sentBefore, Carbon $paidBefore, string $path): void
	{
		$c = $this->counts;
		$this->components->info(($this->option('dry-run') ? 'Dry run, nothing written. ' : '')."Newest shop order {$newest->format('d.m.Y H:i')} UTC.");
		$this->components->twoColumnDetail('Orders', (string) $c['orders']);
		$this->components->twoColumnDetail('Lines', "{$c['lines']}, {$c['linked']} on a licence in the catalogue");
		$this->components->twoColumnDetail('Accounts matched by email', (string) $c['matched']);
		$this->components->twoColumnDetail('Accounts created', "{$c['created']}, {$c['passwords']} with their shop password");
		$this->components->twoColumnDetail('Counted as sent before', $sentBefore->format('d.m.Y'));
		$this->components->twoColumnDetail('Counted as paid before', $paidBefore->format('d.m.Y'));
		$this->components->twoColumnDetail('Left to send', (string) $c['outstanding']);
		$this->components->twoColumnDetail('Listed for VIAK', count($this->report).' orders → '.$path);

		if ($this->unknownSkus) {
			arsort($this->unknownSkus);
			$this->newLine();
			$this->line('  Article numbers not in the catalogue (kept on the line as sold): '.count($this->unknownSkus).' numbers, '.array_sum($this->unknownSkus).' lines');
		}

		foreach ($this->findings as $finding) {
			$this->components->warn($finding);
		}
	}
}
