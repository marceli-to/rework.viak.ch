<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Accounts\UpdateProfile;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\UpdateProfileRequest;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Event;
use App\Models\Message;
use App\Support\CancellationPenalty;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * *Mein Profil* and the screens under it ([[08-accounts]], [[09-public-site]]).
 *
 * The student half of legacy's `backend/student/` SPA — four Vue views, 839
 * lines, three API calls to draw a screen whose every value the server already
 * had — rebuilt as server-rendered Blade like the rest of the public site.
 *
 * Everything it needs was built with chunk 08. Nothing here is new behaviour:
 * the profile update is [[UpdateProfile]], cancelling is [[CancelBooking]]
 * through `/api/bookings/{booking}/cancel`, and the documents come off the same
 * policy-gated route the invoices link to.
 */
class StudentPortalController extends Controller
{
	/**
	 * How many documents the landing screen shows before *Alle Dokumente
	 * anzeigen*. Legacy's `StudentResource` takes five and so does this.
	 */
	private const DOCUMENT_PREVIEW = 5;

	/**
	 * The landing screen: the address block, then four collapsibles.
	 *
	 * Legacy's `Index.vue` fetches `/api/student/profile`, `/api/user/settings`
	 * and — for the edit form — the country and gender lists, then renders. All
	 * of it is one query set here.
	 */
	public function index(Request $request, CancellationPenalty $penalty): View
	{
		$user = $request->user()->load('country');

		[$upcoming, $past] = $this->splitBookings($user->bookings()
			->active()
			->with(['event.course', 'event.dates', 'event.location', 'event.experts'])
			->get());

		return view('site.student.profile', [
			'user' => $user,
			'bookmarks' => $user->bookmarks()
				->with(['course', 'dates', 'location', 'experts'])
				->get()
				->sortBy('date')
				->values(),
			'upcoming' => $upcoming,
			'past' => $past,
			'documents' => $user->documents()
				->with('documentable')
				->latest('date')
				->take(self::DOCUMENT_PREVIEW)
				->get(),
			'documentCount' => $user->documents()->count(),
			'penalties' => $this->penalties($upcoming, $penalty),
		]);
	}

	/**
	 * The form, on a screen of its own — `/de/student/profil/bearbeiten`.
	 *
	 * **A sibling of the address screens, not a state of the profile** (Marcel,
	 * 2026-09-22): the form and nothing else, with a *Zurück* where the profile
	 * has its *Logout*. Legacy toggles it in place off `isEdit`, and that is
	 * component state — which does not survive leaving the page, and
	 * *Rechnungsadressen* lives inside the form with links to screens of their
	 * own ([[SiteUrl::studentProfileEdit]]).
	 *
	 * It wants none of what the landing screen loads. No bookings, no bookmarks,
	 * no documents, no penalties — a form asking for a phone number has no use
	 * for a course list, and computing one per visit was the cost of treating
	 * this as the same page.
	 */
	public function edit(Request $request): View
	{
		$user = $request->user()->load('country');

		return view('site.student.edit', [
			'user' => $user,
			'addresses' => $user->addresses()->with('country')->orderBy('company')->orderBy('last_name')->get(),
			'countries' => Country::query()->orderBy('order')->orderBy('name')->get(),
			'genders' => Gender::cases(),
		]);
	}

	/**
	 * What cancelling each upcoming booking would cost, **before** it is
	 * cancelled.
	 *
	 * Legacy's `BookingResource` carries the same two figures and
	 * `confirmBookingCancellation()` builds its sentence out of them — inside
	 * the window the dialog names the amount and the rate, outside it only asks.
	 * Computed here by [[CancellationPenalty]], the same class
	 * [[RaiseCancellationPenalty]] will use, so the dialog cannot promise one
	 * number and the invoice say another.
	 *
	 * @param  Collection<int, Booking>  $bookings
	 * @return array<string, array{applies: bool, amount: string, rate: int}>
	 */
	private function penalties(Collection $bookings, CancellationPenalty $penalty): array
	{
		return $bookings->mapWithKeys(fn ($booking) => [$booking->uuid => [
			'applies' => $penalty->applies($booking),
			'amount' => $penalty->amount($booking),
			// `1.00` and `0.50` as the percentages the sentence quotes.
			'rate' => (int) round((float) $penalty->rate($booking) * 100),
		]])->all();
	}

	/**
	 * The edit form's POST.
	 *
	 * The same [[UpdateProfile]] the API calls, so the email change clears
	 * verification and the credential change wants the current password on both
	 * surfaces. [[UpdateProfileRequest]] has already enforced the second; the
	 * Action checks again because it is called from places that are not this
	 * request, and a `RuntimeException` from it would be a programming error
	 * rather than a customer one — which is why it becomes a field error here
	 * rather than a 500.
	 */
	public function update(UpdateProfileRequest $request, UpdateProfile $update): RedirectResponse
	{
		try {
			$user = $update->execute(
				user: $request->user(),
				attributes: $request->profileAttributes(),
				email: $request->filled('email') ? $request->string('email')->value() : null,
				password: $request->filled('password') ? $request->string('password')->value() : null,
				currentPassword: $request->string('current_password')->value() ?: null,
			);
		} catch (RuntimeException) {
			// `back()` is the form's own URL now, because the POST lands there
			// — no redirect target to keep in step with the view.
			return back()
				->withInput()
				->withErrors(['current_password' => 'Das aktuelle Passwort ist nicht korrekt.']);
		}

		/*
		 * A changed address has to be proven before mail goes to it, and the
		 * customer has to be told so — an unverified address stops the booking
		 * confirmation and the invoice arriving, and somebody who does not know
		 * that will wonder where they went. The API answers the same fact as
		 * `email_verification_required`.
		 */
		/*
		 * **`Dir` and `Deine`, capitalised.** The site addresses the customer
		 * informally and capitalises it throughout — 90 occurrences in legacy's
		 * own copy and not one lowercase — so *Die Annullation wird Dir per
		 * E-Mail bestätigt* and *Deine Merkliste ist leer*. This sentence had it
		 * both ways inside itself, which is the kind of thing that reads as
		 * sloppiness rather than as a style.
		 */
		return redirect(SiteUrl::studentPortal())->with(
			'status',
			$user->hasVerifiedEmail()
				? 'Deine Angaben wurden gespeichert.'
				: 'Deine Angaben wurden gespeichert. Bitte bestätige Deine neue E-Mail-Adresse über den Link, den wir Dir geschickt haben.',
		);
	}

	/** *Meine Dokumente* — the full list behind the landing screen's five. */
	public function documents(Request $request): View
	{
		return view('site.student.documents', [
			'documents' => $request->user()->documents()
				->with('documentable')
				->latest('date')
				->get(),
		]);
	}

	/**
	 * One booked seat: the booking, the course notes, and the course materials.
	 *
	 * **Resolved by the event's uuid**, which is legacy's choice and the uuid a
	 * student already holds from the course page. The booking is then found from
	 * it — and *not finding one is a 404*, which is the object-level check
	 * legacy's `role:admin,student` route did not make on its neighbours
	 * ([[08-accounts]], findings 4 and 5).
	 */
	public function event(Request $request, string $uuid, CancellationPenalty $penalty): View
	{
		$event = Event::query()
			->where('uuid', $uuid)
			->with(['course', 'dates', 'location', 'experts', 'media'])
			->firstOrFail();

		$booking = $request->user()->bookings()
			->where('event_id', $event->id)
			->latest('booked_at')
			->first();

		if ($booking === null) {
			// A seat the customer does not hold, told apart from a seat that
			// does not exist only by whoever is asking — so it is the same 404
			// either way.
			throw new NotFoundHttpException;
		}

		/*
		 * The thread and the materials are [[MessagePolicy]]'s and
		 * [[MediaPolicy]]'s call, not this screen's — and both want a **live**
		 * seat, where the booking row above is still shown for a cancelled one.
		 *
		 * That difference is deliberate. A cancelled booking is the customer's
		 * own history and they should be able to open it; the course's notes
		 * and its materials belong to the people actually on the course. Asking
		 * the policy here rather than restating the rule keeps the screen and
		 * the endpoints answering the same question.
		 */
		// `[Message::class, $event]` because the ability is on
		// [[MessagePolicy]] and the argument is an Event — passing the Event
		// alone resolves `EventPolicy`, which has no `viewForEvent` and so
		// denies **silently**. Same call the API makes.
		$belongs = $request->user()->can('viewForEvent', [Message::class, $event]);

		return view('site.student.event', [
			'event' => $event,
			'booking' => $booking,
			'penalty' => [
				'applies' => $penalty->applies($booking),
				'amount' => $penalty->amount($booking),
				'rate' => (int) round((float) $penalty->rate($booking) * 100),
			],
			'messages' => $belongs
				? $event->messages()->with(['author', 'media'])->latest()->get()
				: collect(),
			// Course materials the expert uploaded. 13 rows across the archive
			// — zips of models and textures, workshop PDFs — and until now
			// unreachable, because `Event` carried no `media()` relation for
			// `port:media` to have filled ([[08-accounts]]).
			'files' => $belongs ? $event->media : collect(),
		]);
	}

	/**
	 * *Gebuchte Kurse* and *Absolvierte Kurse*, **split on the event's date**.
	 *
	 * Legacy splits on two spatie flags instead: `bookings` is everything
	 * `notFlagged('isConcluded')` and `bookingsParticipated` is
	 * `flagged('isConcluded')->flagged('hasParticipated')`. `isConcluded` is set
	 * in one place — `EventClosedHandler`, and **only for a booking already
	 * flagged `hasParticipated`** — so a seat nobody ticked off never leaves the
	 * first list.
	 *
	 * Measured against the 2026-09-11 snapshot: **67 active bookings on courses
	 * that have already happened are still listed as *Gebuchte Kurse* on the
	 * live site**, across 63 students, the oldest from March 2023 — each with a
	 * live *Annullieren* button beside it, and cancelling one would fire the
	 * 100 % penalty rule against a course that ran two years ago. 91 active
	 * bookings carry no `isConcluded` at all; 67 of them are in the past.
	 *
	 * The date is the fact. Attendance is a separate question, it is recorded by
	 * the participation confirmation rather than by a list heading, and the
	 * rework does not carry the flags across at all ([[01-schema]]).
	 *
	 * @param  Collection<int, Booking>  $bookings
	 * @return array{0: Collection<int, Booking>, 1: Collection<int, Booking>}
	 */
	private function splitBookings(Collection $bookings): array
	{
		// `Event::scopeUpcoming` counts today as upcoming — legacy's `date >
		// today` made an event happening *today* neither upcoming nor past —
		// and the same boundary has to hold here, because these two lists are
		// meant to cover everything between them.
		$upcoming = $bookings
			->filter(fn ($booking) => $booking->event->date->isToday() || $booking->event->date->isFuture())
			->sortBy(fn ($booking) => $booking->event->date)
			->values();

		$past = $bookings
			->reject(fn ($booking) => $booking->event->date->isToday() || $booking->event->date->isFuture())
			->sortByDesc(fn ($booking) => $booking->event->date)
			->values();

		return [$upcoming, $past];
	}
}
