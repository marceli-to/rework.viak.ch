<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Builds public URLs with the locale prefix and the locale's own path segments
 * ([[00-foundation]]).
 *
 * Exists so no view hardcodes `/de/kurs/…`. Turning EN on then means adding a
 * locale to `config/site.php`, not finding every link.
 */
final class SiteUrl
{
	public static function segment(string $key, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return config("site.segments.{$locale}.{$key}", $key);
	}

	/** The course list — `/de/kurse`. */
	public static function courses(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('courses', $locale);
	}

	/** One course — `/de/kurs/{slug}`. */
	public static function course(string $slug, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('course', $locale).'/'.$slug;
	}

	/**
	 * A checkout step — `/de/checkout/basket`.
	 *
	 * **Legacy's own URLs, English step names and all.** `config/site.php`
	 * carries a `basket` segment of `warenkorb`, which somebody meant as the
	 * German form; legacy never used it and serves `/de/checkout/basket`
	 * throughout ([[09-public-site]]). Parity wins here, so the segment stays
	 * unused until it is decided on rather than being adopted by accident —
	 * these pages are behind a login and nothing indexes them, which is why it
	 * is a small question rather than an SEO one.
	 */
	public static function checkout(string $step = 'basket', ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('checkout', $locale).'/'.$step;
	}

	/**
	 * The student portal, and the screens under it ([[08-accounts]]).
	 *
	 * **Legacy's own two trees, kept whole** — `/de/student/profil` for a
	 * student and `/de/experte/profil` for an expert, rather than one `/de/konto`
	 * for both. Three accounts hold Admin + Expert + Student and one holds
	 * Admin + Student, so the two portals are not alternative views of the same
	 * thing: they show different data and a dual-role user needs both at once.
	 * The role in the path is what tells them apart, and it is also what the
	 * role middleware is already guarding.
	 *
	 * Every segment comes from `config/site.php` rather than being written into
	 * the path here, so `/en/student/profile/documents` exists the day `'en'`
	 * joins `site.locales` (Marcel, 2026-09-22). That is the one thing that
	 * differs from legacy, which spells both languages out twice in
	 * `routes/web.php`.
	 */
	public static function studentPortal(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('student', $locale).'/'.self::segment('profile', $locale);
	}

	/**
	 * The profile's edit form — `/de/student/profil/bearbeiten`.
	 *
	 * **A screen of its own, not a panel** (Marcel, 2026-09-22). Legacy toggles
	 * the form in place off `isEdit`, which is component state, and that does
	 * not survive leaving the page — so *Rechnungsadressen* lives inside the
	 * panel, its links go to screens of their own, and the way out and the way
	 * back do not line up: add an address, come back, and the list you added it
	 * to is shut. The address is there and invisible until you click the pencil
	 * again. Legacy has the same hole and an SPA hides it, because its
	 * router-links unmount `Index.vue` and it remounts with `isEdit = false`.
	 *
	 * Giving the form a URL fixes it at the root rather than papering over it,
	 * and it takes the last piece of JavaScript off this screen: no toggle, no
	 * `x-show`, no `x-cloak`. The pencil is a link, *Abbrechen* is a link, and
	 * the browser's own back button does what it looks like it should.
	 */
	public static function studentProfileEdit(?string $locale = null): string
	{
		return self::studentPortal($locale).'/'.self::segment('edit', $locale);
	}

	/** *Meine Dokumente* — `/de/student/profil/dokumente`. */
	public static function studentDocuments(?string $locale = null): string
	{
		return self::studentPortal($locale).'/'.self::segment('documents', $locale);
	}

	/**
	 * One booked seat — `/de/student/profil/kurs/veranstaltung/{uuid}`.
	 *
	 * The uuid is the **event's**, not the booking's, which is legacy's choice
	 * and worth keeping: a student has at most one live booking per event, and
	 * the uuid is the one already on the course page.
	 */
	public static function studentEvent(string $uuid, ?string $locale = null): string
	{
		return self::studentPortal($locale)
			.'/'.self::segment('course', $locale)
			.'/'.self::segment('event', $locale)
			.'/'.$uuid;
	}

	/** *Adresse erfassen* — `/de/student/profil/adresse/erstellen`. */
	public static function studentAddressCreate(?string $locale = null): string
	{
		return self::studentPortal($locale)
			.'/'.self::segment('address', $locale)
			.'/'.self::segment('create', $locale);
	}

	/** One saved address — `/de/student/profil/adresse/bearbeiten/{uuid}`. */
	public static function studentAddressEdit(string $uuid, ?string $locale = null): string
	{
		return self::studentPortal($locale)
			.'/'.self::segment('address', $locale)
			.'/'.self::segment('edit', $locale)
			.'/'.$uuid;
	}

	/**
	 * The expert portal — `/de/experte/profil`.
	 *
	 * Here so the header can point at it; its screens are the second pass
	 * ([[09-public-site]]).
	 */
	public static function expertPortal(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('expert', $locale).'/'.self::segment('profile', $locale);
	}

	/**
	 * Where the header's *Profil* points, which depends on who is looking
	 * ([[08-accounts]]).
	 *
	 * Legacy's `MenuItemProfile` does the same three-way choice and adds a
	 * fourth case this does not have: a `selected-role` in the session, set by a
	 * role-picker screen that four accounts see after logging in. That screen is
	 * not built, so the precedence below decides for them — **student first**,
	 * because a multi-role account browsing the public site is the one buying,
	 * and the other portals are one link away from there.
	 *
	 * Until now the icon pointed at `/dashboard` for everyone, **including a
	 * guest** — so *Profil* on the live rebuild sent a signed-out visitor to the
	 * admin SPA shell rather than to the login screen.
	 */
	public static function profileFor(?User $user, ?string $locale = null): string
	{
		if ($user === null) {
			return route('login');
		}

		if ($user->hasRole(Role::Student)) {
			return self::studentPortal($locale);
		}

		if ($user->hasRole(Role::Expert)) {
			return self::expertPortal($locale);
		}

		// Admin-only: the dashboard is theirs and has no public portal.
		return '/dashboard';
	}

	public static function home(?string $locale = null): string
	{
		return '/'.($locale ?? app()->getLocale());
	}

	/** The absolute form, on the host the site is actually indexed under. */
	public static function canonical(string $path): string
	{
		return 'https://'.config('site.canonical_host').$path;
	}
}
