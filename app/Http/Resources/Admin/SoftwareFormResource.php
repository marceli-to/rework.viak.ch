<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Requests\Admin\SaveSoftwareRequest;
use App\Models\Software;
use App\Support\Recommendations;
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
			// *Seite ansehen* in the form's «Was ist …?» section: only once there is a page.
			'about_url' => filled($this->getTranslation('full_description', 'de', false))
				? SiteUrl::softwareAbout($this->getTranslation('slug', 'de'), 'de')
				: null,
			// And in «Lizenzen»: only while it sells something (#71).
			'licences_url' => $this->products()->listed()->exists()
				? SiteUrl::licences($this->getTranslation('slug', 'de'), 'de')
				: null,
			// Why it is not on the site, said above the form (Marcel, 2026-11-12):
			// 3ds Max is only a program courses name, and the form says so.
			'offline' => match (true) {
				! $this->publish => 'Nicht auf der Website: nicht publiziert.',
				! Software::query()->onSite()->whereKey($this->getKey())->exists() => 'Nicht auf der Website: kein publizierter Kurs und keine Lizenz im Shop.',
				default => null,
			},
			'title' => $de('title'),
			'publish' => $this->publish,
			'featured' => $this->featured,
			// What keeps it from being deleted ([[SoftwareController::destroy]]).
			'courses_count' => $this->courses()->count(),
			'products_count' => $this->products()->count(),
		];

		foreach (SaveSoftwareRequest::RICH as $field) {
			$form[$field] = $de($field);
		}
		foreach (SaveSoftwareRequest::PLAIN as $field) {
			$form[$field] = $de($field);
		}
		$form['licence_note'] = $de('licence_note');

		$form['plugin'] = $this->plugin;
		$form['categories'] = $this->categories->pluck('uuid')->all();
		$form['hosts'] = $this->hosts->pluck('uuid')->all();
		$form += Recommendations::form($this->resource);
		$form['seo_description'] = $de('seo_description');
		$form['seo_tags'] = $de('seo_tags');

		return $form;
	}
}
