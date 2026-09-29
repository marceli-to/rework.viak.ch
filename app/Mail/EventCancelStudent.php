<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Support\SiteUrl;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * *Kursabsage – …* to a student whose seat went with the course ([[10-mail]]).
 * Legacy's own, with the course's next two dates to book instead — those
 * published as well as upcoming, so none of them is a link to nothing.
 */
class EventCancelStudent extends VIAKMail
{
	public function __construct(public readonly Booking $booking)
	{
		parent::__construct();
	}

	public function envelope(): Envelope
	{
		return new Envelope(subject: 'Kursabsage – '.$this->course());
	}

	public function content(): Content
	{
		$event = $this->booking->event;
		$course = $event->course;

		$next = Event::query()
			->where('course_id', $course->id)
			->whereDate('date', '>', $event->date)
			->upcoming()
			->active()
			->published()
			->with('dates')
			->orderBy('date')
			->take(2)
			->get()
			->map(fn (Event $date) => $date->dates->sortBy('date')->map(fn ($day) => $day->date->format('d.m.Y'))->implode('/'));

		return new Content(markdown: 'mail.event.cancel', with: [
			'recipient' => 'student',
			'booking' => $this->booking,
			'user' => $this->booking->user,
			'course' => $this->course(),
			'dates' => EventFacts::dates($event),
			'experts' => EventFacts::experts($event),
			'nextDates' => $next->all(),
			'coursePage' => url(SiteUrl::course($course->getTranslation('slug', 'de'), 'de')),
			'courses' => url(SiteUrl::courses('de')),
			// Money already paid, as a code ([[IssueCreditCode]]).
			'credit' => DiscountCode::query()->where('booking_id', $this->booking->getKey())->first(),
		]);
	}

	private function course(): string
	{
		return $this->booking->event->course->getTranslation('title', 'de');
	}
}
