<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Neue Anmeldung für …* to each expert of the date and to the office
 * ([[10-mail]]). Legacy's `BookingCreatedInfoExpert` and
 * `BookingCreatedInfoAdmin`, which differed only in the link an expert gets.
 *
 * `$recipient` is the expert, or for the office whoever holds its address, if
 * anyone does; legacy looked the office up the same way and greeted *Hallo*
 * with no name when nobody did.
 */
class BookingCreatedInfo extends VIAKMail
{
	public function __construct(
		public readonly Booking $booking,
		public readonly ?User $recipient,
		public readonly bool $toExpert,
	) {
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Neue Anmeldung für '.$this->course());
	}

	public function content(): Content
	{
		$event = $this->booking->event;

		return new Content(markdown: 'mail.booking.created-info', with: [
			'booking' => $this->booking,
			'greeting' => $this->recipient?->first_name ?? '',
			'course' => $this->course(),
			'dates' => EventFacts::dates($event),
			'place' => EventFacts::place($event),
			'expertPage' => $this->toExpert ? url(SiteUrl::expertEvent($event->uuid, 'de')) : null,
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
