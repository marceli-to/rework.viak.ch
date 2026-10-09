<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Requests\Admin\SaveSoftwareRequest;
use App\Models\Software;
use App\Support\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A software as the dashboard's form holds it, the shape
 * [[SaveSoftwareRequest]] takes back ([[05-licences]]).
 *
 * @mixin Software
 */
class SoftwareFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$de = fn (string $field) => $this->getTranslation($field, 'de', false) ?: '';

		$form = [
			'uuid' => $this->uuid,
			'url' => SiteUrl::software($this->getTranslation('slug', 'de'), 'de'),
			'title' => $de('title'),
			'subtitle' => $de('subtitle'),
			'publish' => $this->publish,
			'featured' => $this->featured,
			// What keeps it from being deleted ([[SoftwareController::destroy]]).
			'courses_count' => $this->courses()->count(),
			'products_count' => $this->products()->count(),
		];

		foreach (SaveSoftwareRequest::RICH as $field) {
			$form[$field] = $de($field);
		}

		$form['categories'] = $this->categories->pluck('uuid')->all();
		$form['seo_description'] = $de('seo_description');
		$form['seo_tags'] = $de('seo_tags');

		return $form;
	}
}
