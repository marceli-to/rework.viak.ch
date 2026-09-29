<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Documents\RenderParticipantList;
use App\Actions\Media\AttachMedia;
use App\Actions\Media\DeleteMedia;
use App\Actions\Media\UploadMedia;
use App\Actions\Messages\PostMessage;
use App\Enums\EventState;
use App\Enums\Role;
use App\Exceptions\SeatNotAvailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadEventMediaRequest;
use App\Http\Requests\Messages\PostEventMessageRequest;
use App\Http\Resources\Admin\EventRowResource;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Support\MessageHtml;
use App\Support\RichText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A course date's own page on the dashboard ([[07-dashboard]], step 7) —
 * legacy's `course/event/Show.vue`: the date, its participants with the tick
 * that decides who gets the participation confirmation (Marcel, 2026-09-29),
 * *Teilnehmer hinzufügen*, the participant list as PDF, *Nachrichten* and
 * *Kurs-Dokumente*.
 *
 * **UI over what the expert portal already does**: the same Actions and the
 * same two requests ([[PostEventMessageRequest]], [[UploadEventMediaRequest]]),
 * which the expert portal routes by `{uuid}` and this binds as `{event}`.
 */
class EventPageController extends Controller
{
	public function show(Event $event): JsonResponse
	{
		$event->load(['course', 'dates', 'location', 'experts', 'media'])->loadCount([
			'bookings' => fn ($query) => $query->active(),
			'bookings as rentals_taken_count' => fn ($query) => $query->active()->where('has_rental', true),
		]);

		$participants = $event->bookings()->active()->with('user')->get()
			->sortBy(fn (Booking $booking) => mb_strtolower($booking->user->last_name.' '.$booking->user->first_name))
			->values()
			->map(fn (Booking $booking) => [
				'uuid' => $booking->uuid,
				'student' => $booking->user->uuid,
				'name' => $booking->user->name,
				'city' => $booking->user->city,
				// Legacy's fallback: the person's firm, else the one they bill to.
				'company' => $booking->user->company ?: ($booking->invoice_address['company'] ?? null),
				'email' => $booking->user->email,
				'has_rental' => $booking->has_rental,
				'participated' => $booking->hasParticipated(),
			]);

		return response()->json(['data' => [
			'event' => new EventRowResource($event),
			'course' => [
				'uuid' => $event->course->uuid,
				'number' => $event->course->number,
				'title' => $event->course->getTranslation('title', 'de'),
			],
			'participants' => $participants,
			'messages' => $event->messages()->with(['author', 'media'])->withCount('recipients')->latest()->get()
				->map(fn (Message $message) => [
					'uuid' => $message->uuid,
					'subject' => $message->subject,
					// Through the site's allowlist, as both portals show it: ported
					// bodies were never sanitised on the way in ([[RichText]]).
					'body' => (string) RichText::render($message->body),
					'author' => $message->author?->name,
					'created_at' => $message->created_at?->toDateString(),
					'recipients' => $message->recipients_count,
					'attachments' => $message->media->map(fn (Media $media) => $this->file($media))->all(),
				]),
			'files' => $event->media->map(fn (Media $media) => $this->file($media)),
		]]);
	}

	/**
	 * Ticked as attended, or not. Not once the date is closed: the
	 * confirmations went out then, to the seats ticked at the time.
	 */
	public function participation(Request $request, Booking $booking): JsonResponse
	{
		$participated = $request->validate(['participated' => ['required', 'boolean']])['participated'];

		abort_if($booking->isCancelled(), 422, 'Diese Buchung ist annulliert.');
		abort_if(in_array($booking->event->state, [EventState::Closed, EventState::Cancelled], true), 422, 'Diese Veranstaltung ist abgeschlossen oder abgesagt.');

		$booking->forceFill(['participated_at' => $participated ? ($booking->participated_at ?? now()) : null])->save();

		return response()->json(['data' => ['uuid' => $booking->uuid, 'participated' => $booking->hasParticipated()]]);
	}

	/**
	 * *Teilnehmer hinzufügen* — legacy's `Api\Dashboard\BookingController::create`
	 * through [[CreateBookingForUser]]: no basket, no discount, today's fee, and
	 * the booking mail as at checkout. **Not a deactivated account** (#16, noted
	 * as owed in *Step 6 — students*), and not on a date that is over or
	 * called off. Capacity is not checked, as legacy did not: an admin adding
	 * someone to a full course means to.
	 */
	public function book(Request $request, Event $event, CreateBookingForUser $create): JsonResponse
	{
		$uuid = $request->validate(['student' => ['required', 'uuid']])['student'];
		$student = User::query()->withRole(Role::Student)->where('uuid', $uuid)->firstOrFail();

		abort_if($student->deactivated_at !== null, 422, 'Dieses Konto ist deaktiviert.');
		abort_if(in_array($event->state, [EventState::Closed, EventState::Cancelled], true), 422, 'Diese Veranstaltung ist abgeschlossen oder abgesagt.');

		try {
			$booking = $create->execute($event, $student);
		} catch (SeatNotAvailable $problem) {
			abort(422, $problem->getMessage());
		}

		return response()->json(['data' => ['uuid' => $booking->uuid]], 201);
	}

	/** *Teilnehmerliste (PDF)* — the expert portal's, for any course date. */
	public function participants(Event $event, RenderParticipantList $list): Response
	{
		return response($list->execute($event), 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="'.$list->filename($event).'"',
			'Cache-Control' => 'private, no-store',
		]);
	}

	/**
	 * A note to everyone on the course — the expert portal's composer
	 * ([[ExpertPortalController::storeMessage]]): the files come with the post
	 * and are attached after it, and the mails go out on [[MessagePosted]].
	 * The dashboard's editor always sends HTML, and only HTML is taken.
	 */
	public function message(PostEventMessageRequest $request, Event $event, PostMessage $post, UploadMedia $upload, AttachMedia $attach): JsonResponse
	{
		$uploads = array_map(fn ($file) => $upload->execute($file), $request->file('attachments') ?? []);

		$message = $post->execute(
			event: $event,
			author: $request->user(),
			subject: $request->string('subject')->value(),
			// Cleaned here whatever `body_format` says: the request cleans only
			// what announces itself as HTML, and this path never takes text.
			body: MessageHtml::sanitize($request->string('body')->value()),
			copyToAuthor: $request->boolean('copy_to_me'),
		);

		$attach->execute($uploads, $message);

		return response()->json(['data' => ['uuid' => $message->uuid]], 201);
	}

	/** *Kurs-Dokumente* — [[UploadMedia]] then [[AttachMedia]], as the portal does. */
	public function upload(UploadEventMediaRequest $request, Event $event, UploadMedia $upload, AttachMedia $attach): JsonResponse
	{
		$attached = $attach->execute(array_map(fn ($file) => $upload->execute($file), $request->file('files')), $event);

		return response()->json(['data' => array_map(fn (Media $media) => $this->file($media), $attached)], 201);
	}

	/** Only the date's own files: a message's attachment is not reached here. */
	public function removeFile(Event $event, Media $media, DeleteMedia $delete): JsonResponse
	{
		abort_unless($media->mediable_type === $event->getMorphClass() && $media->mediable_id === $event->id, 404);

		$delete->execute($media);

		return response()->json(status: 204);
	}

	/** @return array{uuid: string, name: string, size: int, url: string} */
	private function file(Media $media): array
	{
		return [
			'uuid' => $media->uuid,
			'name' => $media->original_name,
			'size' => $media->size,
			'url' => route('media.download', $media),
		];
	}
}
