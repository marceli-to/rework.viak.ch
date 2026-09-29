<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Buchung Mietcomputer für …* to the office, when a seat comes with a laptop ([[10-mail]]). Legacy's own. */
class RentalAddedInfoAdmin extends VIAKMail
{
	public function __construct(public readonly Booking $booking)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Buchung Mietcomputer für '.$this->course());
	}

	public function content(): Content
	{
		return new Content(markdown: 'mail.booking.rental-added-info', with: [
			'booking' => $this->booking,
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->booking->event),
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
