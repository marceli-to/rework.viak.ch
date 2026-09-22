<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\Event;
use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

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
		$event = Event::query()->where('uuid', $this->route('uuid'))->first();

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
			'files.*' => ['file', 'max:'.(32 * 1024)],
		];
	}

	/** @return array<string, string> */
	public function attributes(): array
	{
		return ['files' => 'Dokumente', 'files.*' => 'Dokument'];
	}
}
