<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Support\ScrubTarget;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Anonymises a local copy of the production database in place ([[01-schema]]).
 *
 * A production dump carries 468 real people — names, addresses, e-mail, and
 * 448 invoices. Working against realistic data is worth a lot; keeping the real
 * identities on a development machine is not. This scrubs the identities and
 * leaves everything the port reconciles against untouched.
 *
 * Intended flow:
 *
 *     mysql … viak_legacy < production.sql
 *     php artisan db:scrub
 *     php artisan port:courses
 *
 * ## What is preserved
 *
 * Every id, uuid, foreign key, timestamp, money column, status and row count.
 * Reconciliation compares those, so scrubbing must not touch them.
 *
 * ## What is replaced
 *
 * Customer identities: names, company, address, phone, e-mail, passwords,
 * tokens, the free-text invoice/booking address blobs, uploaded document
 * filenames, queued mail recipients, and message bodies (which carry course
 * links and occasionally participant names).
 *
 * ## What is deliberately left alone
 *
 * `team_members` and the expert bio fields. Those are editorial copy already
 * published on viak.ch, not customer data, and blanking them makes the team and
 * expert pages useless to review. Say so out loud rather than let it look like
 * an oversight.
 *
 * Replacements are deterministic — seeded per row id — so two runs of the same
 * dump produce the same people, and a finding you chase on Monday is still the
 * same row on Tuesday.
 */
class ScrubLegacyDatabase extends Command
{
	protected $signature = 'db:scrub
		{--connection=legacy : The connection holding the copy to scrub}
		{--force : Skip the confirmation prompt}';

	protected $description = 'Anonymise a local copy of the production database in place';

	private const DEV_PASSWORD = 'password';

	/** @var array<string, array<int, string>> */
	private array $selfBumping = [];

	public function handle(): int
	{
		$connection = DB::connection($this->option('connection'));
		$database = $connection->getDatabaseName();

		if (! $this->isSafeTarget($database)) {
			return self::FAILURE;
		}

		$this->components->info("Scrubbing [{$database}] on connection [{$this->option('connection')}].");
		$this->line('  Identities are replaced. Ids, dates, money, statuses and row counts are not.');
		$this->newLine();

		if (! $this->option('force') && ! $this->confirm("Scrub [{$database}] in place?", false)) {
			$this->components->warn('Aborted.');

			return self::FAILURE;
		}

		$counts = [];

		$connection->transaction(function () use ($connection, &$counts): void {
			$counts['users'] = $this->scrubUsers($connection);
			$counts['user_addresses'] = $this->scrubUserAddresses($connection);
			$counts['invoices'] = $this->scrubFreeTextAddresses($connection, 'invoices');
			$counts['bookings'] = $this->scrubFreeTextAddresses($connection, 'bookings');
			$counts['user_documents'] = $this->scrubDocuments($connection);
			$counts['messages'] = $this->scrubMessages($connection);
			$counts['jobs'] = $this->scrubJobs($connection);
		});

		$this->report($counts);

		return self::SUCCESS;
	}

	private function isSafeTarget(string $database): bool
	{
		if (app()->environment('production')) {
			$this->components->error('Refusing to run in the production environment.');

			return false;
		}

		if (ScrubTarget::isObviouslyACopy($database)) {
			return true;
		}

		$this->components->error("Refusing to scrub [{$database}].");
		$this->line('  The target must be an obvious copy — its name has to contain one of: '
			.implode(', ', ScrubTarget::MARKERS).'.');
		$this->line('  Rename the database rather than loosening this check.');

		return false;
	}

	private function scrubUsers(Connection $connection): int
	{
		$password = Hash::make(self::DEV_PASSWORD);
		$touched = 0;

		foreach ($connection->table('users')->orderBy('id')->cursor() as $user) {
			$person = $this->person((int) $user->id);

			$connection->table('users')->where('id', $user->id)->update($this->preservingTimestamps($connection, 'users', $user, [
				'firstname' => $person['firstname'],
				'name' => $person['name'],
				'company' => $user->company === null || $user->company === '' ? $user->company : $person['company'],
				'street' => $person['street'],
				'street_no' => $person['street_no'],
				'zip' => $person['zip'],
				'city' => $person['city'],
				'phone' => $user->phone === null || $user->phone === '' ? $user->phone : $person['phone'],
				// .test is reserved and never resolves, so a stray queue run
				// cannot reach a real person.
				'email' => "user{$user->id}@example.test",
				'password' => $password,
				'remember_token' => null,
				'confirm_token' => null,
			]));

			$touched++;
		}

		return $touched;
	}

	private function scrubUserAddresses(Connection $connection): int
	{
		$touched = 0;

		foreach ($connection->table('user_addresses')->orderBy('id')->cursor() as $address) {
			// Seeded off the owning user, so a student's saved addresses stay
			// recognisably theirs.
			$person = $this->person((int) $address->user_id, (int) $address->id);

			$connection->table('user_addresses')->where('id', $address->id)->update($this->preservingTimestamps($connection, 'user_addresses', $address, [
				'firstname' => $person['firstname'],
				'name' => $person['name'],
				'company' => $address->company === null || $address->company === '' ? $address->company : $person['company'],
				'street' => $person['street'],
				'street_no' => $person['street_no'],
				'zip' => $person['zip'],
				'city' => $person['city'],
			]));

			$touched++;
		}

		return $touched;
	}

	/**
	 * `invoices.invoice_address` and `bookings.invoice_address` are free-text
	 * blobs — sometimes newline-separated, sometimes `<br>`. Both shapes occur
	 * in production, and the PDF renderer depends on them, so the replacement
	 * keeps whichever separator the row already used.
	 */
	private function scrubFreeTextAddresses(Connection $connection, string $table): int
	{
		$touched = 0;

		$rows = $connection->table($table)
			->whereNotNull('invoice_address')
			->where('invoice_address', '!=', '')
			->orderBy('id')
			->cursor();

		foreach ($rows as $row) {
			$person = $this->person((int) ($row->user_id ?? $row->id), (int) $row->id);
			$separator = str_contains((string) $row->invoice_address, '<br>') ? '<br>' : "\n";

			$connection->table($table)->where('id', $row->id)->update($this->preservingTimestamps($connection, $table, $row, [
				'invoice_address' => implode($separator, [
					"{$person['firstname']} {$person['name']}",
					"{$person['street']} {$person['street_no']}",
					"{$person['zip']} {$person['city']}",
				]),
			]));

			$touched++;
		}

		return $touched;
	}

	/**
	 * Filenames double as a display label and often contain the student's name.
	 * The `uri` is left pointing where it pointed: the files themselves are not
	 * in the dump, and rewriting the path would only hide that.
	 */
	private function scrubDocuments(Connection $connection): int
	{
		$touched = 0;

		foreach ($connection->table('user_documents')->orderBy('id')->cursor() as $document) {
			$extension = pathinfo((string) $document->name, PATHINFO_EXTENSION);

			$connection->table('user_documents')->where('id', $document->id)->update($this->preservingTimestamps($connection, 'user_documents', $document, [
				'name' => "dokument-{$document->id}".($extension !== '' ? ".{$extension}" : ''),
			]));

			$touched++;
		}

		return $touched;
	}

	/**
	 * Bodies carry course logistics — meeting links, access codes, occasionally
	 * a participant's name. Subjects are generic ("Infos zum Kurs vom …") and
	 * are kept, because a list of messages with identical subjects tells you
	 * nothing when reviewing the screen.
	 */
	private function scrubMessages(Connection $connection): int
	{
		$body = implode("\n", [
			'<p>Platzhalter nach Anonymisierung.</p>',
			'<p>Der ursprüngliche Text enthielt Kursinformationen und wurde entfernt.</p>',
		]);

		return $connection->table('messages')->update(['body' => $body]);
	}

	private function scrubJobs(Connection $connection): int
	{
		return $connection->table('jobs')
			->whereNotNull('recipient')
			->update(['recipient' => 'queue@example.test']);
	}

	/**
	 * Columns MySQL rewrites by itself on any UPDATE.
	 *
	 * `invoices.due_at` is one: the 2023 migration added it as a bare
	 * `timestamp`, so MySQL applied its implicit first-TIMESTAMP rule and gave
	 * it `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. Any write to
	 * an invoice row — including this scrub — silently resets the payment
	 * deadline to now. Writing the existing value back keeps the scrub honest;
	 * the underlying schema problem is the live application's, and is recorded
	 * in [[03-invoices]].
	 *
	 * @return array<int, string>
	 */
	private function selfBumpingColumns(Connection $connection, string $table): array
	{
		return $this->selfBumping[$table] ??= $connection->table('information_schema.columns')
			->where('table_schema', $connection->getDatabaseName())
			->where('table_name', $table)
			->where('extra', 'like', '%on update%')
			->pluck('COLUMN_NAME')
			->all();
	}

	/**
	 * @param  array<string, mixed>  $values
	 * @return array<string, mixed>
	 */
	private function preservingTimestamps(Connection $connection, string $table, object $row, array $values): array
	{
		foreach ($this->selfBumpingColumns($connection, $table) as $column) {
			$values[$column] = $row->{$column};
		}

		return $values;
	}

	/**
	 * Deterministic fake person. Same dump in, same people out.
	 *
	 * @return array<string, string>
	 */
	private function person(int $seed, int $variant = 0): array
	{
		$faker = fake('de_CH');
		$faker->seed($seed * 1000 + $variant);

		return [
			'firstname' => $faker->firstName(),
			'name' => $faker->lastName(),
			'company' => $faker->company(),
			'street' => $faker->streetName(),
			'street_no' => (string) $faker->numberBetween(1, 180),
			'zip' => (string) $faker->numberBetween(1000, 9999),
			'city' => $faker->city(),
			'phone' => $faker->phoneNumber(),
		];
	}

	/** @param array<string, int> $counts */
	private function report(array $counts): void
	{
		$this->newLine();
		$this->components->info('Scrubbed');

		$this->table(
			['table', 'rows'],
			collect($counts)->map(fn (int $rows, string $table) => [$table, $rows])->values()->all(),
		);

		$this->components->info('Every account now has the password: '.self::DEV_PASSWORD);
		$this->components->warn('Left untouched on purpose: team_members and the expert bio fields — published editorial copy, not customer data.');
	}
}
