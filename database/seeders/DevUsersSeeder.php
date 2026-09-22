<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\EventState;
use App\Enums\Gender;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The three accounts the rework is clicked through with — one per role
 * ([[.rework/Test-Users.md]]).
 *
 * **Local only, and it refuses to run anywhere else.** It creates an admin with
 * a password written into this file, which is defensible exactly as long as the
 * database it writes to is a scratch copy on somebody's laptop. The guard below
 * is the thing that keeps it true; do not soften it.
 *
 * Idempotent, so it can be re-run after `port:*` rebuilds the database without
 * producing a second set. The student's own data is **rebuilt** each time — the
 * bookings, addresses and documents it attaches are fixtures, not history, and
 * leaving yesterday's behind makes the screens read differently every run.
 */
class DevUsersSeeder extends Seeder
{
	/*
	 * **No `WithoutModelEvents`**, which the stock `DatabaseSeeder` has.
	 * [[HasUuid]] fills `uuid` from a `creating` hook, and muting model events
	 * mutes that too — every insert then fails on *Field 'uuid' doesn't have a
	 * default value*, from a trait that looks like it has nothing to do with
	 * events.
	 */

	/**
	 * From `~/.claude/bin/genpass`, first candidate, unedited.
	 *
	 * Written down on purpose: these accounts live in a local database that
	 * anyone holding this repository can rebuild, so the password guards
	 * nothing and hiding it would only mean nobody could use them. The same
	 * sentence is *not* true of anything in production, and nothing here should
	 * ever reach it.
	 */
	public const PASSWORD = 'FLAW-GLEE-CENT-BOSS-GAVE-HOOK-FUSE';

	public function run(): void
	{
		if (! app()->environment('local')) {
			throw new \RuntimeException(
				'DevUsersSeeder creates accounts with a published password and runs only in `local`.'
			);
		}

		$student = $this->account('dev@viak.test', 'Dev', 'Student', ['student']);
		$this->account('dev-expert@viak.test', 'Dev', 'Expert', ['expert']);
		$this->account('dev-admin@viak.test', 'Dev', 'Admin', ['admin']);

		$this->give($student);
	}

	/**
	 * One account, created or brought back into line.
	 *
	 * `updateOrCreate` on the email so a re-run resets the password rather than
	 * failing on the unique index — the common reason to run this twice is
	 * having forgotten it.
	 *
	 * @param  array<int, string>  $roles
	 */
	private function account(string $email, string $firstName, string $lastName, array $roles): User
	{
		$user = User::withTrashed()->updateOrCreate(['email' => $email], [
			'first_name' => $firstName,
			'last_name' => $lastName,
			'company' => 'Jamon Digital',
			'gender' => Gender::Other,
			'street' => 'Bahnhofstrasse',
			'street_no' => '1',
			'zip' => '8001',
			'city' => 'Zürich',
			'country_code' => 'ch',
			'phone' => '+41 44 000 00 00',
			'password' => Hash::make(self::PASSWORD),
			'email_verified_at' => now(),
			'deleted_at' => null,
		]);

		foreach ($roles as $role) {
			DB::table('role_user')->insertOrIgnore(['user_id' => $user->id, 'role' => $role]);
		}

		return $user;
	}

	/**
	 * Everything the student portal has a branch for, so the screens can be
	 * looked at rather than reasoned about.
	 *
	 * Hung off **real ported events**, because a factory-built course reads
	 * nothing like the site: no expert, no location, a made-up title. Where the
	 * port has not been run there is nothing to hang it on, and the seeder says
	 * so rather than inventing one.
	 */
	private function give(User $student): void
	{
		$this->clear($student);

		$student->addresses()->createMany([
			[
				'first_name' => 'Anna', 'last_name' => 'Muster', 'company' => 'Muster AG',
				'street' => 'Bahnhofstrasse', 'street_no' => '12',
				'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
			],
			[
				'first_name' => 'Anna', 'last_name' => 'Muster',
				'street' => 'Seeweg', 'street_no' => '4',
				'zip' => '6000', 'city' => 'Luzern', 'country_code' => 'ch',
			],
		]);

		$events = $this->events();

		if ($events === null) {
			$this->command?->warn('No ported events — the student gets no bookings. Run `port:courses` first.');

			return;
		}

		[$upcoming, $withRental, $offersRental, $past, $bookmark] = $events;

		// *Gebuchte Kurse*, three of them: a plain seat, one holding a laptop,
		// and one an event that has laptops to offer.
		$this->book($student, $upcoming);
		$this->book($student, $withRental, rental: true);
		$this->book($student, $offersRental);

		// *Absolvierte Kurse* — the split is on the event's date
		// ([[StudentPortalController::splitBookings]]).
		$booking = $this->book($student, $past);

		// *Merkliste*.
		$student->bookmarks()->syncWithoutDetaching([$bookmark->id]);

		$this->documents($student, $booking);
	}

	/**
	 * Five events that between them cover the portal's branches, or null.
	 *
	 * **Two confirmed and two planned**, deliberately: the state line is the one
	 * thing on a row that says whether the course is actually happening, and it
	 * reads *Kurs findet statt* in green for the first and *Kurs offen, wird
	 * bestätigt* in orange for the second ([[x-site.event-state]]). One of each
	 * is the only way to look at both.
	 *
	 * The snapshot has 36 upcoming events and **3 of them confirmed**, so this
	 * takes what it can get rather than insisting on a shape the data may not
	 * have.
	 *
	 * @return array{0: Event, 1: Event, 2: Event, 3: Event, 4: Event}|null
	 */
	private function events(): ?array
	{
		$upcoming = fn (?EventState $state) => Event::query()
			->when($state, fn ($query) => $query->where('state', $state))
			->whereDate('date', '>=', today())
			->orderBy('date')
			->with('course');

		$confirmed = $upcoming(EventState::Confirmed)->take(2)->get();
		$planned = $upcoming(EventState::Planned)->take(2)->get();

		$chosen = $confirmed->concat($planned)
			// Whatever is missing from either, made up from the rest.
			->concat($upcoming(null)->take(4)->get())
			->unique('id')
			->take(4)
			->values();

		$past = Event::query()->whereDate('date', '<', today())->orderByDesc('date')->first();

		if ($chosen->count() < 4 || $past === null) {
			return null;
		}

		// Two of them need laptops to rent; `rentals_available` is the event's
		// own switch and a course in a room without machines cannot sell one.
		$chosen[1]->update(['rentals_available' => 4]);
		$chosen[2]->update(['rentals_available' => 4]);

		return [$chosen[0], $chosen[1], $chosen[2], $past, $chosen[3]];
	}

	private function book(User $student, Event $event, bool $rental = false): Booking
	{
		return Booking::create([
			'number' => str_pad((string) random_int(900000, 999999), 6, '0', STR_PAD_LEFT),
			'event_id' => $event->id,
			'user_id' => $student->id,
			'course_fee' => $event->fee ?: 600,
			'has_rental' => $rental,
			'rental_fee' => $rental ? config('invoice.rental_fee') : 0,
			'booked_at' => now()->subDays(30),
		]);
	}

	/**
	 * One of each kind, so *Dokumente* shows both the invoice row with its
	 * number, total and status and the plainer participation confirmation.
	 *
	 * **The files are not there**, so *Download* answers 404 — these are rows,
	 * not PDFs. Generating one would mean running the invoice pipeline, which is
	 * a different thing to be testing.
	 */
	private function documents(User $student, Booking $booking): void
	{
		$invoice = Invoice::create([
			'number' => str_pad((string) random_int(900000, 999999), 6, '0', STR_PAD_LEFT),
			'user_id' => $student->id,
			'status' => InvoiceStatus::Paid,
			'date' => today()->subDays(20),
			'net' => $booking->course_fee,
			'discount' => 0,
			'vat' => 0,
			'grand_total' => $booking->course_fee,
		]);

		InvoiceItem::create([
			'invoice_id' => $invoice->id,
			'type' => InvoiceItemType::Course,
			'itemable_type' => Booking::class,
			'itemable_id' => $booking->id,
			'description' => $booking->event->course->getTranslation('title', 'de'),
			'net' => $booking->course_fee,
			'vat_rate' => 0,
			'vat' => 0,
			'total' => $booking->course_fee,
		]);

		UserDocument::create([
			'user_id' => $student->id,
			'type' => DocumentType::Invoice,
			'filename' => 'viak-rechnung-dev.pdf',
			'date' => today()->subDays(20),
			'documentable_type' => Invoice::class,
			'documentable_id' => $invoice->id,
		]);

		UserDocument::create([
			'user_id' => $student->id,
			'type' => DocumentType::ParticipationConfirmation,
			'filename' => 'viak-teilnahme-dev.pdf',
			'date' => today()->subDays(10),
			'documentable_type' => Booking::class,
			'documentable_id' => $booking->id,
		]);
	}

	/** Last run's fixtures, so a re-run does not stack them. */
	private function clear(User $student): void
	{
		$student->documents()->each(fn (UserDocument $document) => $document->delete());

		$student->bookings()->withTrashed()->get()->each(function (Booking $booking): void {
			$booking->invoiceItems()->get()->each(function (InvoiceItem $item): void {
				$item->invoice?->forceDelete();
				$item->forceDelete();
			});

			$booking->forceDelete();
		});

		$student->addresses()->forceDelete();
		$student->bookmarks()->detach();
	}
}
