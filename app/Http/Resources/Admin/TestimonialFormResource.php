<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A testimonial as the dashboard's form and list hold it — the shape
 * [[SaveTestimonialRequest]] takes back ([[07-dashboard]]).
 *
 * @mixin Testimonial
 */
class TestimonialFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'quote' => $this->getTranslation('quote', 'de', false) ?: '',
			'name' => $this->name,
			'context' => $this->getTranslation('context', 'de', false) ?: '',
			'subject' => $this->subjectKey(),
			'publish' => $this->publish,

			'subject_label' => $this->subjectLabel(),

			// Read, never sent: where it stands. The form keeps anything that is
			// not a schema field as `meta` ([[useResourceForm]]).
			'placements' => $this->placements(),
		];
	}
}
