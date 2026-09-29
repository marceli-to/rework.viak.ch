<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Actions\Media\UploadMedia;
use App\Models\Event;
use App\Models\Media;
use App\Support\DocumentTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * *Dokumente hochladen* — course materials, from the expert portal
 * ([[08-accounts]], [[09-public-site]]).
 *
 * Gated by [[MediaPolicy::createForEvent]], which is the same rule as posting a
 * note to the course. Legacy's `EventFileController::store` carries no
 * `authorize()` at all and its route is `role:admin,expert`, so any of the 18
 * accounts holding the Expert role can add a file to any course in the archive.
 */
class UploadEventMediaRequest extends FormRequest
{
	public function authorize(): bool
	{
		// The portal routes by `{uuid}`, the dashboard binds `{event}`.
		$event = $this->route('event') ?? Event::query()->where('uuid', $this->route('uuid'))->first();

		if ($event === null) {
			return false;
		}

		return $this->user()?->can('createForEvent', [Media::class, $event]) ?? false;
	}

	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			'files' => ['required', 'array', 'max:10'],

			/*
			 * 32 MB each, the number legacy's composer label quotes and enforces
			 * nowhere. The materials in the archive are zips of 3D models and
			 * textures, so a per-file cap is the one that has to be generous;
			 * PHP's own `upload_max_filesize` is the ceiling underneath it and
			 * is what a deployment has to agree with ([[Todo]]).
			 */
			'files.*' => ['file', 'max:'.(32 * 1024), ...DocumentTypes::rules()],

			// Legacy's *Bezeichnung*, one per file in the files' order, each
			// optional (Marcel, 2026-09-29).
			'captions' => ['sometimes', 'array'],
			'captions.*' => ['nullable', 'string', 'max:255'],
		];
	}

	/**
	 * Each file uploaded, carrying the caption typed beside it. Set here,
	 * before [[AttachMedia]] saves the row, because it skips a file it cannot
	 * find and the order after it would no longer be the files'.
	 *
	 * @return array<int, Media>
	 */
	public function uploads(UploadMedia $upload): array
	{
		return array_map(function (UploadedFile $file, int $index) use ($upload): Media {
			$media = $upload->execute($file);
			$caption = trim((string) ($this->input('captions')[$index] ?? ''));
			$media->caption = $caption === '' ? null : $caption;

			return $media;
		}, $this->file('files'), array_keys($this->file('files')));
	}

	/** @return array<string, string> */
	public function attributes(): array
	{
		return ['files' => 'Dokumente', 'files.*' => 'Dokument'];
	}

	/** @return array<string, string> */
	public function messages(): array
	{
		return [
			'files.*.extensions' => DocumentTypes::message(),
			'files.*.mimetypes' => DocumentTypes::message(),
		];
	}
}
