<?php

declare(strict_types=1);

namespace App\Http\Requests\Messages;

use App\Models\Media;
use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

class PostMessageRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user()?->can('create', [Message::class, $this->route('event')]) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'subject' => ['required', 'string', 'max:255'],
			'body' => ['required', 'string'],
			'attachments' => ['sometimes', 'array', 'max:10'],
			'attachments.*' => ['uuid', 'exists:media,uuid'],
			'copy_to_me' => ['sometimes', 'boolean'],
		];
	}

	/** @return array<int, Media> */
	public function attachments(): array
	{
		return Media::query()
			->whereIn('uuid', (array) $this->input('attachments', []))
			->get()
			->all();
	}
}
