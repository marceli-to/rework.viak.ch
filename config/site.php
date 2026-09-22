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

			/*
			 * The portals ([[08-accounts]]). Legacy's own two trees —
			 * `/de/student/profil` and `/de/experte/profil` — kept whole,
			 * because a user holding two roles needs two of them and the role
			 * in the path is what separates them. Only the *segments* move in
			 * here, so `/en/student/profile/…` exists the day EN does.
			 */
			'student' => 'student',
			'profile' => 'profil',
			'event' => 'veranstaltung',
			'address' => 'adresse',
			'create' => 'erstellen',
			'edit' => 'bearbeiten',

			/*
			 * The two screens under an expert's course, and **they are English
			 * inside the German path** — `/de/experte/profil/kurs/veranstaltung/
			 * {uuid}/message` and `/file-upload`. Legacy's own spelling, kept
			 * for the same reason `/de/checkout/basket` is: parity, and nothing
			 * behind a login is indexed, so it is a small question rather than
			 * an SEO one ([[SiteUrl::checkout]]).
			 */
			'message' => 'message',
			'upload' => 'file-upload',
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
			'student' => 'student',
			'profile' => 'profile',
			'event' => 'event',
			'address' => 'address',
			'create' => 'create',
			'edit' => 'edit',
			'message' => 'message',
			'upload' => 'file-upload',
		],
	],

	/*
	 * Defaults for the head, from legacy's `config/seo.php` verbatim.
	 *
	 * Note that the site's name for SEO is **not** `APP_NAME`. Legacy keeps the
	 * legal entity — "Visualisierungs-Akademie Schweiz GmbH" — in `APP_NAME` for
	 * mail, and uses the short form in titles. The rework's `APP_NAME` is the
	 * short form, so the two agree; this stays as the single place a page title's
	 * suffix comes from.
	 */
	'seo' => [
		'description' => 'Visualisierungs-Akademie Schweiz GmbH - Seminare, Workshops und Individualschulungen',

		/*
		 * Carried across verbatim. Google has ignored `meta keywords` since 2009
		 * and it does nothing — but removing something the live site sends is a
		 * decision, not a port, so it comes across and can go deliberately later.
		 */
		'keywords' => 'Visualisierungs-Akademie Schweiz: Seminare, Kurse, Schulung, Weiterbildung, Workshops, Graphic Recording und Visualisierungen in Strategie, Change, Innovation, Vision, 3D-Software, Zeichnen',

		'image' => '/assets/img/viak-og.jpg',
	],

];
