<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** *Buchung Mietcomputer für …* to the student, a laptop added later ([[10-mail]]). Legacy's own. */
class RentalAdded extends VIAKMail
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
		return new Content(markdown: 'mail.booking.rental-added', with: [
			'course' => $this->course(),
			'dates' => EventFacts::dates($this->booking->event),
			'portal' => url(SiteUrl::customerPortal('de')),
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
