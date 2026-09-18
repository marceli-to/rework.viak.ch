<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Messages\PostMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\PostMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Event;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The notes on one course ([[08-accounts]]).
 *
 * Every method authorises against the *event*, which is the check legacy
 * skipped: its equivalents were gated by role alone, so a student could read
 * and write the thread of a course they had never booked.
 */
class MessageController extends Controller
{
	public function index(Event $event): AnonymousResourceCollection
	{
		$this->authorize('viewForEvent', [Message::class, $event]);

		return MessageResource::collection(
			$event->messages()->with(['author', 'media'])->withCount('recipients')->latest()->get()
		);
	}

	public function store(PostMessageRequest $request, Event $event, PostMessage $post): JsonResponse
	{
		$message = $post->execute(
			event: $event,
			author: $request->user(),
			subject: $request->string('subject')->value(),
			body: $request->string('body')->value(),
			attachments: $request->attachments(),
			copyToAuthor: $request->boolean('copy_to_me'),
		);

		return (new MessageResource($message->load(['author', 'media'])->loadCount('recipients')))
			->response()
			->setStatusCode(201);
	}

	public function destroy(Message $message): JsonResponse
	{
		$this->authorize('delete', $message);

		$message->delete();

		return response()->json(status: 204);
	}
}
