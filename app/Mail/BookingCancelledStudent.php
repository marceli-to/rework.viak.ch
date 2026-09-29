<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Annullationsbestätigung*, when cancelling costs nothing ([[10-mail]]). Legacy's `BookingCancelled`. */
class BookingCancelledStudent extends VIAKMail
{
	public function __construct(public readonly Booking $booking)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Annullationsbestätigung – '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.booking.cancellation', with: [
			'booking' => $this->booking,
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->booking->event),
			'courses' => url(SiteUrl::courses('de')),
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
