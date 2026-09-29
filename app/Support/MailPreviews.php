<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\Courses\CreateCourse;
use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Mail\AccountInvitation;
use App\Mail\BookingCancelledInfoAdmin;
use App\Mail\BookingCancelledStudent;
use App\Mail\BookingCancelledWithPenalty;
use App\Mail\BookingCompleted;
use App\Mail\BookingCreatedInfo;
use App\Mail\EmailVerification;
use App\Mail\EventCancelExpert;
use App\Mail\EventCancelOrConfirmReminder;
use App\Mail\EventCancelStudent;
use App\Mail\EventClosedStudent;
use App\Mail\EventConfirmationExpert;
use App\Mail\EventConfirmationStudent;
use App\Mail\EventMessageExpert;
use App\Mail\EventMessageStudent;
use App\Mail\ParticipantsChange;
use App\Mail\PasswordReset;
use App\Mail\RentalAdded;
use App\Mail\RentalAddedInfoAdmin;
use App\Mail\RentalCancelledInfoAdmin;
use App\Mail\VIAKMail;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDocument;
use Closure;

/**
 * Every VIAK mail, built from one fixture course ([[10-mail]], layer 5):
 * what `/dev/mails` renders, for putting each beside legacy's on production.
 *
 * The fixtures are written to the database, because the mails query it (a
 * credit code, the next dates); **the caller rolls them back**
 * ([[MailPreviewController]]). One entry per branch a view takes, so a mail
 * with and without its credit code are both on the list.
 */
final class MailPreviews
{
	private Event $event;

	private User $expert;

	private User $student;

	private Booking $booking;

	private Booking $rental;

	private Booking $credited;

	private UserDocument $invoice;

	private UserDocument $certificate;

	private Message $post;

	public function __construct()
	{
		$course = app(CreateCourse::class)->execute([
			'title' => ['de' => 'Rhino Einstiegskurs'],
			'fee' => '890.00',
			'online' => false,
			'publish' => true,
			'order' => 0,
		]);

		$location = Location::query()->published()->value('id');
		$this->event = $this->date($course->id, $location, 30);
		$this->date($course->id, $location, 60);
		$this->date($course->id, $location, 90);

		$this->expert = User::factory()->expert()->create(['first_name' => 'Kevin', 'last_name' => 'Muster', 'email' => 'vorschau-kevin@viak.test']);
		$this->event->experts()->attach($this->expert);

		$this->student = User::factory()->student()->create(['first_name' => 'Anna', 'last_name' => 'Beispiel', 'email' => 'vorschau-anna@viak.test']);
		$this->booking = $this->seat();
		$this->rental = $this->seat(['has_rental' => true, 'rental_fee' => '80.00']);
		$this->credited = $this->seat(['cancelled_at' => now()]);
		DiscountCode::factory()->create(['code' => 'GUT890', 'amount' => '890.00', 'booking_id' => $this->credited->id, 'usage_limit' => 1, 'valid_from' => today(), 'valid_to' => today()->addYear()]);

		$invoice = Invoice::factory()->create([
			'number' => app(InvoiceNumber::class)->next(),
			'user_id' => $this->student->id,
			'status' => InvoiceStatus::Open,
			'net' => '890.00',
			'grand_total' => '890.00',
		]);
		$this->invoice = $this->document(DocumentType::Invoice, $invoice, 'viak-rechnung-vorschau.pdf');
		$this->certificate = $this->document(DocumentType::ParticipationConfirmation, $this->booking, 'viak-teilnahmebestaetigung-vorschau.pdf');

		$this->post = Message::factory()->create([
			'event_id' => $this->event->id,
			'user_id' => $this->expert->id,
			'subject' => 'Raum und Anreise',
			'body' => '<p>Wir treffen uns im Kursraum im 2. Stock. Bitte die <strong>Testversion</strong> vorab installieren.</p>',
		]);
	}

	/**
	 * In the flow table's order, keyed for the URL.
	 *
	 * @return array<string, array{0: string, 1: Closure(): VIAKMail}> key => [when it is sent, the mail]
	 */
	public function all(): array
	{
		return [
			'registrierung' => ['Student registers, or an address changes', fn () => new EmailVerification($this->student)],
			'zugang' => ['Admin creates an account', fn () => new AccountInvitation($this->student)],
			'passwort' => ['Password reset', fn () => new PasswordReset($this->student, 'vorschau')],
			'buchung' => ['Booking made: student', fn () => new BookingCompleted($this->booking)],
			'buchung-experte' => ['Booking made: each expert', fn () => new BookingCreatedInfo($this->booking, $this->expert, toExpert: true)],
			'buchung-buero' => ['Booking made: office', fn () => new BookingCreatedInfo($this->booking, null, toExpert: false)],
			'miete-buero' => ['Booking made with a laptop, or one added: office', fn () => new RentalAddedInfoAdmin($this->rental)],
			'miete' => ['Laptop added later: student', fn () => new RentalAdded($this->rental)],
			'miete-storno' => ['Laptop dropped: office', fn () => new RentalCancelledInfoAdmin($this->rental)],
			'annullation' => ['Student cancels, no penalty', fn () => new BookingCancelledStudent($this->booking)],
			'annullation-gutschrift' => ['Student cancels, no penalty, invoice already paid', fn () => new BookingCancelledStudent($this->credited)],
			'annullation-kosten' => ['Student cancels late: penalty invoice attached', fn () => new BookingCancelledWithPenalty($this->booking, '445.00', 50, false, $this->invoice)],
			'annullation-kosten-bezahlt' => ['Student cancels late, invoice already paid', fn () => new BookingCancelledWithPenalty($this->credited, '445.00', 50, true, null)],
			'abmeldung-buero' => ['Student cancels: office', fn () => new BookingCancelledInfoAdmin($this->event, null)],
			'min' => ['Seats reach the minimum: office', fn () => new ParticipantsChange($this->event, 'min')],
			'max' => ['Seats reach the maximum: office', fn () => new ParticipantsChange($this->event, 'max')],
			'unter-min' => ['Seats drop below the minimum: office', fn () => new ParticipantsChange($this->event, 'belowMin')],
			'reminder' => ['10 days out, still planned: office', fn () => new EventCancelOrConfirmReminder($this->event)],
			'bestaetigung' => ['Date confirmed: student, with the invoice', fn () => new EventConfirmationStudent($this->booking, $this->invoice)],
			'bestaetigung-ohne-rechnung' => ['Date confirmed: student, seat not billable', fn () => new EventConfirmationStudent($this->booking, null)],
			'bestaetigung-experte' => ['Date confirmed: each expert', fn () => new EventConfirmationExpert($this->event, $this->expert)],
			'absage' => ['Date cancelled: student, with the next two dates', fn () => new EventCancelStudent($this->booking)],
			'absage-gutschrift' => ['Date cancelled: student, invoice already paid', fn () => new EventCancelStudent($this->credited)],
			'absage-experte' => ['Date cancelled: each expert', fn () => new EventCancelExpert($this->event)],
			'teilnahme' => ['Date closed: each student who attended', fn () => new EventClosedStudent($this->booking, $this->certificate)],
			'nachricht' => ['Expert posts a message: each student', fn () => new EventMessageStudent($this->post)],
			'nachricht-kopie' => ['Expert posts a message: the author\'s copy', fn () => new EventMessageExpert($this->post)],
		];
	}

	private function date(int $courseId, ?int $locationId, int $daysOut): Event
	{
		$event = Event::factory()->create([
			'course_id' => $courseId,
			'location_id' => $locationId,
			'date' => now()->addDays($daysOut)->toDateString(),
		]);
		$event->dates()->createMany([
			['date' => now()->addDays($daysOut)->toDateString(), 'time_start' => '09:00', 'time_end' => '17:00'],
			['date' => now()->addDays($daysOut + 1)->toDateString(), 'time_start' => '09:00', 'time_end' => '17:00'],
		]);

		return $event;
	}

	/** @param  array<string, mixed>  $attributes */
	private function seat(array $attributes = []): Booking
	{
		return Booking::factory()->create([
			'number' => app(BookingNumber::class)->next(),
			'event_id' => $this->event->id,
			'user_id' => $this->student->id,
			'course_fee' => '890.00',
			...$attributes,
		])->setRelation('event', $this->event);
	}

	private function document(DocumentType $type, Invoice|Booking $about, string $filename): UserDocument
	{
		return UserDocument::create([
			'user_id' => $this->student->id,
			'type' => $type,
			'filename' => $filename,
			'date' => now()->toDateString(),
			'documentable_type' => $about->getMorphClass(),
			'documentable_id' => $about->id,
		]);
	}
}
