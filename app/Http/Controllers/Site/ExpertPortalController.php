<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Accounts\UpdateProfile;
use App\Actions\Documents\RenderParticipantList;
use App\Actions\Media\AttachMedia;
use App\Actions\Media\DeleteMedia;
use App\Actions\Media\UploadMedia;
use App\Actions\Messages\PostMessage;
use App\Enums\EventState;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\UpdateProfileRequest;
use App\Http\Requests\Media\UploadEventMediaRequest;
use App\Http\Requests\Messages\PostEventMessageRequest;
use App\Models\Country;
use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

/**
 * The expert portal — `/de/experte/profil` and the four screens under it
 * ([[08-accounts]], [[09-public-site]]).
 *
 * Legacy's `backend/expert/` SPA: four Vue views, 578 lines, two API calls to
 * draw a screen whose every value the server already had. Server-rendered Blade
 * like the rest of the public site, and the sibling of
 * [[StudentPortalController]] — same article, same collapsibles, same row.
 *
 * **Where the two differ is what a course *is* to each of them.** A student's
 * course is a seat they bought, so their screen leads with the booking and what
 * can still be done to it. An expert's course is a room they will stand in, so
 * theirs leads with who is coming — and carries the two things only an expert
 * does, which are writing to the class and giving it files.
 *
 * Every write here already existed: [[UpdateProfile]], [[PostMessage]],
 * [[UploadMedia]] and [[AttachMedia]]. What is new is a policy per screen, which
 * is the whole point of the chunk — legacy gates all four of these by role alone
 * ([[08-accounts]], findings 4 and 5).
 */
class ExpertPortalController extends Controller
{
	/**
	 * The landing screen: the address block, then *Bevorstehende Kurse* and
	 * *Vergangene Kurse*.
	 *
	 * **The courses are the ones they teach**, off `event_expert` — not the ones
	 * they booked. Legacy reaches them through `User::upcomingEvents()`, a
	 * `belongsToMany(Event::class)` on the same pivot, and sorts the result in
	 * the browser after having asked the database to sort it.
	 */
	public function index(Request $request): View
	{
		$user = $request->user()->load('country');

		[$upcoming, $past] = $this->splitEvents($user);

		return view('site.expert.profile', [
			'user' => $user,
			'upcoming' => $upcoming,
			'past' => $past,
		]);
	}

	/**
	 * The profile form, on a screen of its own — `/de/experte/profil/bearbeiten`.
	 *
	 * The student's form minus *Rechnungsadressen*, which is exactly the
	 * difference legacy's two copies of it have. An expert is not a customer:
	 * they hold no invoice addresses, so there is nothing here that links out of
	 * the form ([[SiteUrl::expertProfileEdit]]).
	 */
	public function edit(Request $request): View
	{
		return view('site.expert.edit', [
			'user' => $request->user()->load('country'),
			'countries' => Country::query()->orderBy('order')->orderBy('name')->get(),
			'genders' => Gender::cases(),
		]);
	}

	/**
	 * The form's POST, which is [[StudentPortalController::update]] with a
	 * different redirect.
	 *
	 * Deliberately the same Action and the same FormRequest rather than the
	 * expert-specific copy legacy keeps: `Api/ExpertController::update` and
	 * `Api/StudentController::update` differ only in which fields they validate,
	 * and both change an email address without confirming it or resetting
	 * `email_verified_at` ([[08-accounts]], finding 2).
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
			return back()
				->withInput()
				->withErrors(['current_password' => 'Das aktuelle Passwort ist nicht korrekt.']);
		}

		return redirect(SiteUrl::expertPortal())->with(
			'status',
			$user->hasVerifiedEmail()
				? 'Deine Angaben wurden gespeichert.'
				: 'Deine Angaben wurden gespeichert. Bitte bestätige Deine neue E-Mail-Adresse über den Link, den wir Dir geschickt haben.',
		);
	}

	/**
	 * One course: *Informationen*, *Teilnehmer*, *Nachrichten*,
	 * *Kurs-Dokumente*.
	 *
	 * **Authorised against the event**, which is the check legacy's
	 * `role:admin,expert` never makes — and the reason this screen was the one
	 * to settle finding 5 before building: it is where the participant list
	 * lives, and the participant list is names, towns, phone numbers and email
	 * addresses ([[EventPolicy::viewParticipants]]).
	 *
	 * The participants come from the **cancelled** bookings where the event
	 * itself is cancelled, which is legacy's own `$event->isCancelled() ?
	 * $event->cancelledBookings : $event->bookings`. It reads like a trick and
	 * it is the right answer: calling off a course cancels every seat on it
	 * ([[CancelBookingsForEvent]]), so the live list would be empty and the
	 * expert would lose the list of people they have to apologise to.
	 */
	public function event(Request $request, string $uuid): View
	{
		$event = $this->teachable($request->user(), $uuid);

		$bookings = $event->bookings()
			->when(
				$event->state === EventState::Cancelled,
				fn ($query) => $query->whereNotNull('cancelled_at'),
				fn ($query) => $query->active(),
			)
			->with(['user.addresses'])
			->get()
			->sortBy(fn ($booking) => $booking->user->last_name)
			->values();

		return view('site.expert.event', [
			'event' => $event,
			'bookings' => $bookings,
			'messages' => $event->messages()->with(['author', 'media'])->latest()->get(),
			'files' => $event->media,
		]);
	}

	/** *Nachricht erstellen* — the composer, a screen of its own as in legacy. */
	public function createMessage(Request $request, string $uuid): View
	{
		$event = $this->teachable($request->user(), $uuid);

		$this->authorize('create', [Message::class, $event]);

		return view('site.expert.message', [
			'event' => $event,
			// What *Sende eine Nachricht an alle Studenten dieses Kurses* means,
			// counted rather than promised: [[PostMessage]] mails the holders of
			// live seats, so a course with cancellations mails fewer people than
			// it has bookings.
			'recipients' => $event->bookings()->active()->distinct()->count('user_id'),
		]);
	}

	/**
	 * Posting it — [[PostMessage]], the same Action the API calls, so the
	 * recipients are recorded once rather than recomputed from whoever happens
	 * to hold a seat when somebody next opens the thread.
	 *
	 * The attachments are uploaded **with the form** rather than beforehand:
	 * legacy's uploader posts each file to `/api/file` as it is dropped and
	 * sends a list of uuids with the message, which leaves an orphan row behind
	 * every abandoned draft — 11 of its 44 files are attached to nothing at all.
	 * One multipart POST cannot do that.
	 */
	public function storeMessage(
		PostEventMessageRequest $request,
		string $uuid,
		PostMessage $post,
		UploadMedia $upload,
		AttachMedia $attach,
	): RedirectResponse {
		$event = $this->teachable($request->user(), $uuid);

		$uploads = array_map(fn ($file) => $upload->execute($file), $request->file('attachments') ?? []);

		$message = $post->execute(
			event: $event,
			author: $request->user(),
			subject: $request->string('subject')->value(),
			// Already cleaned by the request when it came from the editor;
			// escaped into paragraphs here when it came from the bare
			// textarea ([[PostEventMessageRequest::prepareForValidation]]).
			body: $request->input('body_format') === 'html'
				? $request->string('body')->value()
				: $this->paragraphs($request->string('body')->value()),
			copyToAuthor: $request->boolean('copy_to_me'),
		);

		// **Attached after the post, not through it.** `PostMessage` takes an
		// `attachments` array and associates each row, which is right for the
		// API — where the files were uploaded by an earlier request and are
		// already in `uploads/`. These are not: [[UploadMedia]] leaves a file in
		// `temp/` and [[AttachMedia]] is what moves it across. Handing them to
		// `PostMessage` instead would write rows whose files are in the wrong
		// directory, and `MediaController` would answer 404 for every one.
		$attach->execute($uploads, $message);

		/*
		 * **It is not mailed, and the sentence does not say it is.** Legacy's
		 * composer queues an `EventMessageStudent` to every participant, and
		 * `PostMessage` records exactly who that would be — but there is no
		 * Mailable anywhere in the rework yet, on this path or the checkout's,
		 * and mail is a chunk of its own ([[00-foundation]]). Telling an expert
		 * their class has been written to when nothing has left the building is
		 * the one wrong thing this screen could say.
		 */
		return redirect(SiteUrl::expertEvent($event->uuid))
			->with('status', 'Die Nachricht wurde erfasst.');
	}

	/**
	 * *Teilnehmerliste (PDF)* — the printable form of the list on the screen
	 * ([[08-accounts]], finding 5).
	 *
	 * **The check is [[ExpertPortalController::teachable]]'s**, which every
	 * screen here goes through, and it is the one legacy's route does not make:
	 * `GET /pdf/teilnehmer-liste/{event}` carries `role:admin,expert` and
	 * nothing else, so any of the 18 accounts holding the Expert role can
	 * download the contact details of every student on every course in the
	 * archive.
	 *
	 * Streamed rather than stored. Legacy writes each one into the public
	 * directory and records nothing, which is how 294 loose PDFs of names and
	 * contact details came to sit there ([[RenderParticipantList]]).
	 */
	public function participants(Request $request, string $uuid, RenderParticipantList $list): Response
	{
		$event = $this->teachable($request->user(), $uuid);

		return response($list->execute($event), 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="'.$list->filename($event).'"',
			'Cache-Control' => 'private, no-store',
		]);
	}

	/** *Dokumente hochladen* — the course materials form. */
	public function createUpload(Request $request, string $uuid): View
	{
		$event = $this->teachable($request->user(), $uuid);

		$this->authorize('createForEvent', [Media::class, $event]);

		return view('site.expert.upload', ['event' => $event]);
	}

	/**
	 * Taking them — [[UploadMedia]] then [[AttachMedia]], which is the pair the
	 * dashboard's media field uses.
	 *
	 * The two-step exists because the crop UI works on an upload before it
	 * belongs to anything; here there is nothing to crop and the second step
	 * follows the first immediately. Worth using anyway rather than writing a
	 * third path — `AttachMedia` is what moves the file out of `temp/` and gives
	 * it its place in the order.
	 */
	public function storeUpload(
		UploadEventMediaRequest $request,
		string $uuid,
		UploadMedia $upload,
		AttachMedia $attach,
	): RedirectResponse {
		$event = $this->teachable($request->user(), $uuid);

		$attach->execute(
			array_map(fn ($file) => $upload->execute($file), $request->file('files')),
			$event,
		);

		return redirect(SiteUrl::expertEvent($event->uuid))
			->with('status', 'Die Dokumente wurden hochgeladen.');
	}

	/**
	 * Removing one again.
	 *
	 * [[MediaPolicy::delete]] admits only a file whose owner is the event, so a
	 * message's attachment cannot be reached through this route whatever uuid is
	 * posted — which is the guarantee legacy's `belongs_to_message` flag was
	 * trying to give from the template.
	 */
	public function destroyFile(Request $request, string $uuid, Media $media, DeleteMedia $delete): RedirectResponse
	{
		$event = $this->teachable($request->user(), $uuid);

		$this->authorize('delete', $media);

		abort_unless($media->mediable_id === $event->id, 404);

		$delete->execute($media);

		return redirect(SiteUrl::expertEvent($event->uuid))
			->with('status', 'Das Dokument wurde entfernt.');
	}

	/**
	 * The event this expert teaches, or a 404.
	 *
	 * **A 404 and not a 403**, which is the student portal's rule for the same
	 * reason: a course somebody does not teach and a course that does not exist
	 * are told apart only by whoever is asking, and answering *forbidden*
	 * confirms that the uuid is real.
	 */
	private function teachable(User $user, string $uuid): Event
	{
		$event = Event::query()
			->where('uuid', $uuid)
			->with(['course', 'dates', 'location', 'experts', 'media'])
			->firstOrFail();

		abort_unless($user->can('viewParticipants', $event), 404);

		return $event;
	}

	/**
	 * *Bevorstehende* and *Vergangene Kurse*, split on the event's date.
	 *
	 * The same boundary the student's lists use and `Event::scopeUpcoming`
	 * draws — **today counts as upcoming**. Legacy asks for `date >=` here and
	 * `date <` there, which agrees by accident: its two relations are written
	 * out separately and its student portal splits on a flag instead, which is
	 * where the 67 stale rows come from ([[StudentPortalController::splitBookings]]).
	 *
	 * @return array{0: Collection<int, Event>, 1: Collection<int, Event>}
	 */
	private function splitEvents(User $user): array
	{
		$events = fn () => $user->eventsAsExpert()
			->with(['course', 'dates', 'location'])
			->withCount(['bookings' => fn ($query) => $query->active()]);

		return [
			$events()->upcoming()->get(),
			$events()->past()->get(),
		];
	}

	/**
	 * A textarea's blank lines as paragraphs.
	 *
	 * The body is stored and rendered as HTML — legacy composes it in TinyMCE,
	 * and the student's screen puts it through [[RichText]]. The composer is a
	 * tiptap editor now ([[x-site.editor]]), whose HTML the request cleans;
	 * this is for the `<textarea>` under it, which is what a browser without
	 * JavaScript sends.
	 *
	 * `e()` first: whatever is typed is text, and treating it as markup here is
	 * how a pasted `<script>` would reach every student's inbox. [[RichText]]
	 * strips tags on the way out as well, which is one belt more than legacy has
	 * at either end.
	 */
	private function paragraphs(string $body): string
	{
		$blocks = preg_split('/\R{2,}/', trim($body)) ?: [];

		return collect($blocks)
			->map(fn (string $block) => '<p>'.nl2br(e(trim($block))).'</p>')
			->implode('');
	}
}
