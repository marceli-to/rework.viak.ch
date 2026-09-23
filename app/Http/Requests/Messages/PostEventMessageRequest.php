<?php

declare(strict_types=1);

namespace App\Http\Requests\Messages;

use App\Models\Event;
use App\Models\Message;
use App\Support\DocumentTypes;
use App\Support\MessageHtml;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The expert portal's composer ([[08-accounts]], [[09-public-site]]).
 *
 * The form twin of [[PostMessageRequest]], which the API uses. Two requests
 * rather than one because the two carry different things: the API sends a list
 * of uuids for files that are already uploaded, and a form sends the files
 * themselves in the same multipart POST ([[ExpertPortalController::storeMessage]]).
 *
 * The authorization is identical and is the check legacy has nowhere —
 * `MessageStoreRequest::authorize()` returns `true`, and the route is gated by
 * role alone, so any authenticated student could post to any event
 * ([[08-accounts]], finding 4).
 */
class PostEventMessageRequest extends FormRequest
{
	public function authorize(): bool
	{
		$event = Event::query()->where('uuid', $this->route('uuid'))->first();

		if ($event === null) {
			return false;
		}

		return $this->user()?->can('create', [Message::class, $event]) ?? false;
	}

	/**
	 * The editor's HTML is cleaned **before** it is validated, so `required`
	 * judges what would be stored: an emptied editor sends `<p></p>`, which is
	 * a string and would pass. Without JavaScript `body_format` stays `text`
	 * and the body is left for the controller to escape ([[MessageHtml]],
	 * [[ExpertPortalController::storeMessage]]).
	 */
	protected function prepareForValidation(): void
	{
		if ($this->input('body_format') !== 'html') {
			return;
		}

		$body = MessageHtml::sanitize((string) $this->input('body'));

		$this->merge(['body' => MessageHtml::isBlank($body) ? '' : $body]);
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'subject' => ['required', 'string', 'max:255'],
			'body' => ['required', 'string'],
			'body_format' => ['sometimes', 'in:text,html'],

			/*
			 * *Anhänge (max. 32 MB)*, which is what legacy's label promises and
			 * **not what it enforces** — its uploader sets no limit at all and
			 * the number is a sentence. Said once here, in kilobytes, and the
			 * label reads it back so the two cannot drift.
			 *
			 * `file` rather than `image`: the attachments in the archive are
			 * PDFs and zips of models and textures, not photographs.
			 */
			'attachments' => ['sometimes', 'array', 'max:10'],
			'attachments.*' => ['file', 'max:'.(32 * 1024), ...DocumentTypes::rules()],

			'copy_to_me' => ['sometimes', 'boolean'],
		];
	}

	/** @return array<string, string> */
	public function attributes(): array
	{
		return [
			'subject' => 'Betreff',
			'body' => 'Nachricht',
			'attachments.*' => 'Anhang',
		];
	}

	/** @return array<string, string> */
	public function messages(): array
	{
		return [
			'attachments.*.extensions' => DocumentTypes::message(),
			'attachments.*.mimetypes' => DocumentTypes::message(),
		];
	}
}
