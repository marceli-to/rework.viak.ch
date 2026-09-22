<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'subject' => $this->subject,
			'body' => $this->body,
			'created_at' => $this->created_at?->toIso8601String(),

			'author' => $this->whenLoaded('author', fn () => [
				// `User::name` is an accessor composing `first_name` and
				// `last_name` ([[User]]). This used to read
				// `$this->author->firstname.' '.$this->author->name` — legacy's
				// column names, where `name` held the *surname*. `firstname`
				// does not exist here, so Eloquent returned null and `trim()`
				// swallowed the leading space: the right answer by accident,
				// and one rename away from a stray space in every payload.
				'name' => $this->author->name,
			]),

			// Who it actually went to, as recorded when it was sent — not the
			// event's current bookings, which would rewrite history every time
			// somebody cancelled.
			'recipient_count' => $this->whenCounted('recipients'),

			'attachments' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
				'uuid' => $media->uuid,
				'name' => $media->original_name,
				'size' => $media->size,
			])),
		];
	}
}
