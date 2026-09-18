<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserDocument */
class UserDocumentResource extends JsonResource
{
	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'type' => $this->type->value,
			'label' => $this->type->label(),
			'filename' => $this->filename,
			'date' => $this->date?->toDateString(),

			// A route, not a path. Legacy handed out a public URL under the
			// storage symlink; this one goes through a policy.
			'download_url' => route('documents.show', $this->uuid),
		];
	}
}
