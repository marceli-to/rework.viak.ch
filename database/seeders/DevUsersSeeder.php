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
use App\Models\Message;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

	/** The one course document the seeder writes, named so it can find it again. */
	private const FIXTURE_DOCUMENT = 'dev-kursunterlagen.pdf';

	public function run(): void
	{
		if (! app()->environment('local')) {
			throw new \RuntimeException(
				'DevUsersSeeder creates accounts with a published password and runs only in `local`.'
			);
		}

		$student = $this->account('dev@viak.test', 'Dev', 'Student', ['student']);
		$expert = $this->account('dev-expert@viak.test', 'Dev', 'Expert', ['expert']);
		$this->account('dev-admin@viak.test', 'Dev', 'Admin', ['admin']);

		/*
		 * **A fourth account holding all three**, which is what four production
		 * users hold and what the two URL trees exist for ([[SiteUrl]]). Until
		 * the expert portal landed there was nothing to click on the second
		 * tree, so seeding one would only have proved the header's precedence;
		 * now it is the account that shows both portals at once.
		 */
		$all = $this->account('dev-all@viak.test', 'Dev', 'Mehrfach', ['student', 'expert', 'admin']);

		$this->give($student);

		// **Different courses for the two**, because they are both real experts
		// on the same ported data: pointing them at the same event made the
		// second run of `teach()` add a second message and a second document to
		// a course that already had one.
		$this->teach($expert, offset: 4);
		$this->teach($all, offset: 5);
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

	/**
	 * Enough for the expert portal to draw every branch it has.
	 *
	 * **Two courses the expert teaches**, one ahead and one behind, because the
	 * landing screen is those two lists and an empty one says nothing about the
	 * layout. The upcoming one carries seats, a note and a document, which are
	 * the three collapsibles on the course screen.
	 *
	 * Hung off real ported events for the reason the student's fixtures are: a
	 * factory-built course reads nothing like the site.
	 */
	private function teach(User $expert, int $offset): void
	{
		$expert->eventsAsExpert()->detach();
		Message::where('user_id', $expert->id)->forceDelete();

		$upcoming = Event::query()->whereDate('date', '>=', today())->orderBy('date')->skip($offset)->first();
		$past = Event::query()->whereDate('date', '<', today())->orderByDesc('date')->skip($offset - 3)->first();

		if ($upcoming === null || $past === null) {
			$this->command?->warn('No ported events — the expert gets no courses. Run `port:courses` first.');

			return;
		}

		$expert->eventsAsExpert()->syncWithoutDetaching([$upcoming->id, $past->id]);

		// Three seats on the upcoming one, and a fourth that was cancelled — so
		// the count beside the row reads 3 and the list shows three names. The
		// students are throwaway accounts rather than ported people, because a
		// ported user's data is the record the port is checked against.
		$this->participants($upcoming);

		$message = Message::create([
			'event_id' => $upcoming->id,
			'user_id' => $expert->id,
			'subject' => 'Anreise und Parkplätze',
			'body' => '<p>Der Parkplatz hinter dem Gebäude ist für uns reserviert.</p>'
				.'<p>Bitte bring einen Laptop mit installierter Software mit.</p>',
		]);

		$message->recipients()->attach(
			$upcoming->bookings()->active()->pluck('user_id')->unique()->all(),
			['created_at' => now()],
		);

		/*
		 * One course document. **The file is not there**, so *Download* answers
		 * 404 — the row is what the screen is made of, and putting a real zip in
		 * the repository to prove a download works is a different thing to be
		 * testing.
		 */
		// **Only the fixture row**, matched on its filename. Wiping the event's
		// media outright would take the ported course materials with it —
		// `port:media` wrote 13 rows against events and they are the record the
		// port is checked against ([[Event]]).
		$upcoming->media()->where('file', self::FIXTURE_DOCUMENT)->forceDelete();

		$upcoming->media()->create([
			'uuid' => (string) Str::uuid(),
			'file' => self::FIXTURE_DOCUMENT,
			'original_name' => 'Kursunterlagen.pdf',
			'mime_type' => 'application/pdf',
			'size' => 1_536_000,
			'variant' => 'desktop',
			'sort_order' => 0,
		]);
	}

	/** Four seats on an event, one of them cancelled. */
	private function participants(Event $event): void
	{
		$people = [
			['Antonia', 'Haller', 'Winterthur', 'Haller Architektur'],
			['Beat', 'Wyss', 'Bern', null],
			['Clara', 'Fischer', 'Basel', null],
			['Daniel', 'Roth', 'Luzern', null],
		];

		foreach ($people as $index => [$first, $last, $city, $company]) {
			/*
			 * **Keyed by the event as well as the position.** One set of four
			 * emails across both courses meant the second call to this method
			 * reused the same four accounts — and `forceDelete()` below then
			 * took their seats off the first course to put them on the second,
			 * which is how an expert's own course came back empty.
			 */
			$email = 'dev-teilnehmer-'.$event->id.'-'.$index.'@viak.test';

			$student = User::updateOrCreate(['email' => $email], [
				'first_name' => $first,
				'last_name' => $last,
				'company' => $company,
				'gender' => Gender::Other,
				'street' => 'Musterweg',
				'street_no' => (string) (10 + $index),
				'zip' => '8000',
				'city' => $city,
				'country_code' => 'ch',
				'phone' => '+41 79 000 00 0'.$index,
				'password' => Hash::make(self::PASSWORD),
				'email_verified_at' => now(),
			]);

			DB::table('role_user')->insertOrIgnore(['user_id' => $student->id, 'role' => 'student']);

			$student->bookings()->forceDelete();

			Booking::create([
				'number' => str_pad((string) random_int(800000, 899999), 6, '0', STR_PAD_LEFT),
				'event_id' => $event->id,
				'user_id' => $student->id,
				'course_fee' => $event->fee ?: 600,
				'booked_at' => now()->subDays(20),
				// The last one dropped out, so the count and the list disagree
				// with the raw booking total — which is the thing to be able to
				// look at.
				'cancelled_at' => $index === 3 ? now()->subDays(2) : null,
			]);
		}
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
