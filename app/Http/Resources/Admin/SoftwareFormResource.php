<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Forms\SoftwareAboutPageSchema;
use App\Forms\SoftwareLicencesPageSchema;
use App\Forms\SoftwareOfferPageSchema;
use App\Models\Software;
use App\Support\Recommendations;
use App\Support\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A software as the dashboard's form holds it, the shape
 * [[SaveSoftwareRequest]] and [[SaveSoftwarePageRequest]] take back ([[05-licences]]).
 *
 * @mixin Software
 */
class SoftwareFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$de = fn (string $field) => $this->getTranslation($field, 'de', false) ?: '';

		$onSite = Software::query()->onSite()->whereKey($this->getKey())->exists();

		$form = [
			'uuid' => $this->uuid,
			'url' => SiteUrl::software($this->getTranslation('slug', 'de'), 'de'),
			/*
			 * *Seite ansehen* under each page form's buttons, **only while that
			 * page opens on the site** (Marcel, 2026-11-17): the Angebot while it
			 * is on the site ([[Software::scopeOnSite]]), «Was ist …?» then too and
			 * once it has a text, «Lizenzen» while it sells something
			 * ([[Software::scopeSellsLicences]]). `url` is its address either way.
			 */
			'live_url' => $onSite ? SiteUrl::software($this->getTranslation('slug', 'de'), 'de') : null,
			'about_url' => $onSite && filled($this->getTranslation('full_description', 'de', false))
				? SiteUrl::softwareAbout($this->getTranslation('slug', 'de'), 'de')
				: null,
			'licences_url' => Software::query()->sellsLicences()->whereKey($this->getKey())->exists()
				? SiteUrl::licences($this->getTranslation('slug', 'de'), 'de')
				: null,
			'title' => $de('title'),
			'publish' => $this->publish,
			'featured' => $this->featured,
			// What keeps it from being deleted ([[SoftwareController::destroy]]).
			'courses_count' => $this->courses()->count(),
			'products_count' => $this->products()->count(),
		];

		// Every page's texts, so one resource serves the software form and the
		// three page forms; each form takes its own fields and reads the rest as meta.
		foreach ([SoftwareOfferPageSchema::class, SoftwareAboutPageSchema::class, SoftwareLicencesPageSchema::class] as $page) {
			foreach (array_keys((new $page)->defaults()) as $field) {
				$form[$field] = $de($field);
			}
		}

		$form['plugin'] = $this->plugin;
		$form['categories'] = $this->categories->pluck('uuid')->all();
		$form['hosts'] = $this->hosts->pluck('uuid')->all();
		$form += Recommendations::form($this->resource);

		return $form;
	}
}
