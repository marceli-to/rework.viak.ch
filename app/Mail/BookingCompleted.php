<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Buchungsbestätigung* to the student, on every booking ([[10-mail]]). Legacy's `BookingCompleted`. */
class BookingCompleted extends VIAKMail
{
	public function __construct(public readonly Booking $booking)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Buchungsbestätigung – '.$this->course());
	}

	public function content(): Content
	{
		$event = $this->booking->event;

		return new Content(markdown: 'mail.booking.confirmation', with: [
			'booking' => $this->booking,
			'event' => $event,
			'user' => $this->booking->user,
			'course' => $this->course(),
			'dates' => EventFacts::dates($event),
			'experts' => EventFacts::experts($event),
			'place' => EventFacts::place($event),
			'fee' => EventFacts::money($this->booking->netFee()),
			'portal' => url(SiteUrl::studentPortal('de')),
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
