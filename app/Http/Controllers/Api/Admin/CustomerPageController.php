<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingCancellationReason;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EventRowResource;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserDocument;
use App\Support\CancellationPenalty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A student's own page on the dashboard ([[07-dashboard]], step 7) — legacy's
 * `student/Show.vue`: the address, the booked and the past courses, the
 * documents, and *Annullieren* on each booked seat.
 *
 * **Split on the event's date**, as the student portal splits them
 * ([[CustomerPortalController]]), not on legacy's flags, which kept 67 seats on
 * courses long over under *Gebuchte Kurse* with a live *Annullieren*.
 * Cancelled seats are listed too, which legacy's page did not: an admin who has
 * just cancelled one should see where it went, and why.
 */
class CustomerPageController extends Controller
{
	public function show(User $customer, CancellationPenalty $penalty): JsonResponse
	{
		$customer->load('country');

		/*
		 * **Deleted events and courses included.** Legacy deleted events that
		 * held bookings, and the port kept both soft-deleted: the seat is still
		 * the student's history, and without `withTrashed()` its event is null
		 * and the page fails (Marcel, 2026-09-29, on a ported student). Such a
		 * seat is marked `deleted`, and gets no *Details* and no *Annullieren*.
		 */
		$bookings = $customer->bookings()
			->with([
				'event' => fn ($query) => $query->withTrashed(),
				'event.course' => fn ($query) => $query->withTrashed(),
				'event.dates', 'event.location', 'event.experts',
			])
			->get();

		$row = fn (Booking $booking) => [
			'uuid' => $booking->uuid,
			'deleted' => $booking->event->trashed() || $booking->event->course->trashed(),
			'course' => [
				'number' => $booking->event->course->number,
				'title' => $booking->event->course->getTranslation('title', 'de'),
			],
			'event' => $this->eventRow($booking),
			'has_rental' => $booking->has_rental,
			'participated' => $booking->hasParticipated(),
		];

		return response()->json(['data' => [
			'customer' => [
				'uuid' => $customer->uuid,
				'name' => $customer->name,
				'company' => $customer->company,
				'street' => trim("{$customer->street} {$customer->street_no}"),
				'city' => trim("{$customer->zip} {$customer->city}"),
				'country' => strtolower((string) $customer->country_code) !== 'ch' ? $customer->country?->getTranslation('name', 'de') : null,
				'email' => $customer->email,
				'phone' => $customer->phone,
				'deactivated_at' => $customer->deactivated_at?->toIso8601String(),
			],
			'booked' => $bookings->reject->isCancelled()->filter($this->upcoming(...))
				->sortBy(fn (Booking $booking) => $booking->event->date)->values()
				->map(fn (Booking $booking) => [...$row($booking), 'penalty' => $this->penalty($booking, $penalty)]),
			'past' => $bookings->reject->isCancelled()->reject($this->upcoming(...))
				->sortByDesc(fn (Booking $booking) => $booking->event->date)->values()
				->map($row),
			'cancelled' => $bookings->filter->isCancelled()
				->sortByDesc('cancelled_at')->values()
				->map(fn (Booking $booking) => [
					...$row($booking),
					'cancelled_at' => $booking->cancelled_at->toIso8601String(),
					'reason' => $booking->cancellation_reason?->label(),
				]),
			'documents' => $customer->documents()->with('documentable')->latest('date')->get()
				->map(fn (UserDocument $document) => $this->document($document)),
		]]);
	}

	/**
	 * *Annullieren*, and **the admin says whether the cost is charged** (#14).
	 * `charge_penalty` is asked for every time; it only matters when
	 * [[CancellationPenalty]] finds a cost today, and only then is a waiver
	 * written down as one ([[BookingCancellationReason]]).
	 *
	 * Only a seat on a course still to come, as in the portal: cancelling one
	 * that has run would bill the full fee for a course the student sat.
	 */
	public function cancel(Request $request, Booking $booking, CancelBooking $cancel, CancellationPenalty $penalty): JsonResponse
	{
		$charge = $request->validate(['charge_penalty' => ['required', 'boolean']])['charge_penalty'];

		abort_if($booking->isCancelled(), 422, 'Diese Buchung ist bereits annulliert.');
		abort_if($booking->event === null, 422, 'Diese Veranstaltung wurde gelöscht.');
		abort_if(! $this->upcoming($booking), 422, 'Dieser Kurs hat bereits stattgefunden.');

		$reason = $penalty->applies($booking) && ! $charge
			? BookingCancellationReason::AdministratorWaived
			: BookingCancellationReason::Administrator;

		$invoice = $cancel->execute($booking, $reason);

		return response()->json(['data' => [
			'uuid' => $booking->uuid,
			'reason' => $reason->value,
			'penalty' => $invoice === null ? null : [
				'number' => $invoice->number,
				'grand_total' => $invoice->grand_total,
			],
		]]);
	}

	/** Today counts as still to come, as `Event::scopeUpcoming` has it. */
	private function upcoming(Booking $booking): bool
	{
		return $booking->event->date->isToday() || $booking->event->date->isFuture();
	}

	/**
	 * What cancelling would cost today, before it is cancelled: the same
	 * figures, from the same class, as the bill ([[RaiseCancellationPenalty]]).
	 *
	 * @return array{applies: bool, amount: string, rate: int}
	 */
	private function penalty(Booking $booking, CancellationPenalty $penalty): array
	{
		return [
			'applies' => $penalty->applies($booking),
			'amount' => $penalty->amount($booking),
			'rate' => (int) round((float) $penalty->rate($booking) * 100),
		];
	}

	/** The date as *Kurse* lists it, without the counts the row has no use for here. */
	private function eventRow(Booking $booking): array
	{
		$event = $booking->event->setAttribute('bookings_count', null)->setAttribute('rentals_taken_count', null);

		return (new EventRowResource($event))->resolve();
	}

	/**
	 * One row of *Dokumente*, as the portal's `row/document` draws it: the
	 * course, what the document is, the amount and its status.
	 */
	private function document(UserDocument $document): array
	{
		$invoice = $document->invoice();
		$event = $document->relatedBooking()?->event;

		return [
			'uuid' => $document->uuid,
			'type' => $document->type->label(),
			'date' => $document->date?->toDateString(),
			'course' => $event?->course->getTranslation('title', 'de'),
			'event_date' => $event?->date->toDateString(),
			'number' => $invoice?->number,
			'grand_total' => $invoice?->grand_total,
			'status' => match ($invoice?->status) {
				InvoiceStatus::Paid => 'Bezahlt',
				InvoiceStatus::Open => 'Offen',
				InvoiceStatus::Overdue => 'Fällig',
				InvoiceStatus::Cancelled => 'Storniert',
				default => null,
			},
			'url' => route('documents.show', $document->uuid),
		];
	}
}
