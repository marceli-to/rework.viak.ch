<?php

declare(strict_types=1);

/*
 * The public site ([[00-foundation]]).
 */

return [

	/*
	 * Locales whose URLs exist. **German only** — EN is out of scope for this
	 * rework (Marcel, 2026-09-18) — but the prefix stays, because the legacy
	 * URLs are indexed and prefixed:
	 *
	 *   /de, /de/kurse, /de/kurs/{slug}/{uuid}, /de/kontakt …
	 *
	 * Verified against production: `/kurse` unprefixed returns **404**, so the
	 * prefix is not decorative. Keeping it costs nothing, preserves every
	 * indexed URL, and leaves `/en/` free — which is exactly the property
	 * `04-content.md` wants, where turning EN on is a config change rather than
	 * a rebuild. Add `'en'` here and the routes exist.
	 */
	'locales' => ['de'],

	/*
	 * The host the site is actually indexed under. The repository is named
	 * after `viak.ch`, which **301s to this** — a detail worth not tripping
	 * over when writing canonical tags and the redirect map.
	 */
	'canonical_host' => env('SITE_CANONICAL_HOST', 'visualisierungs-akademie.ch'),

	/*
	 * Path segments per locale, so `/de/kurs/…` can become `/en/course/…`
	 * without either being hardcoded at a call site. The German values are the
	 * legacy ones, unchanged, because they are what is indexed — note `kurse`
	 * plural for the list and `kurs` singular for the detail, which is legacy's
	 * own inconsistency and not ours to tidy.
	 */
	'segments' => [
		'de' => [
			'courses' => 'kurse',
			'course' => 'kurs',
			'experts' => 'experten',
			'expert' => 'experte',
			'contact' => 'kontakt',
			'training' => 'individualschulungen',
			'basket' => 'warenkorb',
			'checkout' => 'checkout',
			'account' => 'konto',
			'documents' => 'dokumente',
		],
		'en' => [
			'courses' => 'courses',
			'course' => 'course',
			'experts' => 'experts',
			'expert' => 'expert',
			'contact' => 'contact',
			'training' => 'individual-training',
			'basket' => 'basket',
			'checkout' => 'checkout',
			'account' => 'account',
			'documents' => 'documents',
		],
	],

];
