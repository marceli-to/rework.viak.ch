<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;

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
	 * *Über uns* — `/de/ueber-uns`: the Über uns text, the experts and the team
	 * on one page (the 2026-09-23 review's Team mockup). It replaced legacy's
	 * Experten page, whose `/de/experten` 301s here ([[04-content]]).
	 */
	public static function about(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('about', $locale);
	}

	/**
	 * One expert — `/de/experte/{slug}/{uuid}`.
	 *
	 * **Legacy's shape, uuid and all**, unlike the course URL. A course has a
	 * stored slug to resolve by, so its uuid form could be retired with a 301;
	 * a person has no slug column, legacy derives it from the name on every
	 * request, and the uuid is the only half that identifies anyone. So the ten
	 * indexed URLs stay exactly as they are, and a stale slug — a renamed
	 * expert — 301s to the current one ([[ExpertController::show]]).
	 */
	public static function expert(User $expert, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('expert', $locale).'/'.self::expertSlug($expert).'/'.$expert->uuid;
	}

	/**
	 * Legacy's `SlugHelper::make()` on the full name: eight German and French
	 * letters spelled out first — *Nähring* is `naehring`, not `nahring` — and
	 * `Str::slug()` for the rest, which is what turns *Güneş* into `guenes`.
	 * Checked against all ten live URLs on 2026-09-24.
	 */
	public static function expertSlug(User $expert): string
	{
		$name = mb_strtolower(trim("{$expert->first_name} {$expert->last_name}"), 'UTF-8');

		return Str::slug(str_replace(
			['ä', 'ö', 'ü', 'é', 'è', 'â', 'à', 'ç'],
			['ae', 'oe', 'ue', 'e', 'e', 'a', 'a', 'c'],
			$name,
		));
	}

	/**
	 * Paying an invoice by card — `/de/zahlung/rechnung/{uuid}`, legacy's URL,
	 * linked from the course confirmation mail ([[10-mail]]).
	 *
	 * **A placeholder page for now.** Legacy's is a Stripe checkout session
	 * (`PaymentController`), not rebuilt yet; the mail carries the button
	 * already so it does not have to change when the page arrives (`Todo.md`,
	 * `Open-Questions.md` #28).
	 */
	public static function invoicePayment(string $uuid, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('payment', $locale).'/'.self::segment('invoice', $locale).'/'.$uuid;
	}

	/**
	 * Firmenschulung — `/de/firmenschulung` (`Open-Questions.md` #25). Legacy's
	 * `/de/individualschulungen` is indexed and 301s here.
	 */
	public static function training(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('training', $locale);
	}

	/** The Kontakt page — `/de/kontakt`. */
	public static function contact(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('contact', $locale);
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
	 * The customer portal, and the screens under it ([[08-accounts]]) —
	 * `/de/konto` (Marcel, 2026-09-30, `12-customers.md`).
	 *
	 * Legacy's was `/de/student/profil`; every account is a customer now, and
	 * that tree 301s here, sub-pages included, so the links in legacy's mails
	 * and people's bookmarks still land (`routes/web.php`). The expert portal
	 * keeps legacy's `/de/experte/profil`: it is a different screen over
	 * different data, and an account can have both.
	 *
	 * Every segment comes from `config/site.php`, so `/en/account/documents`
	 * exists the day `'en'` joins `site.locales`.
	 */
	public static function customerPortal(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('account', $locale);
	}

	/**
	 * The profile's edit form — `/de/konto/bearbeiten`.
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
	public static function customerProfileEdit(?string $locale = null): string
	{
		return self::customerPortal($locale).'/'.self::segment('edit', $locale);
	}

	/** *Meine Dokumente* — `/de/konto/dokumente`. */
	public static function customerDocuments(?string $locale = null): string
	{
		return self::customerPortal($locale).'/'.self::segment('documents', $locale);
	}

	/**
	 * One booked seat — `/de/konto/kurs/veranstaltung/{uuid}`.
	 *
	 * The uuid is the **event's**, not the booking's, which is legacy's choice
	 * and worth keeping: a student has at most one live booking per event, and
	 * the uuid is the one already on the course page.
	 */
	public static function customerEvent(string $uuid, ?string $locale = null): string
	{
		return self::customerPortal($locale)
			.'/'.self::segment('course', $locale)
			.'/'.self::segment('event', $locale)
			.'/'.$uuid;
	}

	/** *Adresse erfassen* — `/de/konto/adresse/erstellen`. */
	public static function customerAddressCreate(?string $locale = null): string
	{
		return self::customerPortal($locale)
			.'/'.self::segment('address', $locale)
			.'/'.self::segment('create', $locale);
	}

	/** One saved address — `/de/konto/adresse/bearbeiten/{uuid}`. */
	public static function customerAddressEdit(string $uuid, ?string $locale = null): string
	{
		return self::customerPortal($locale)
			.'/'.self::segment('address', $locale)
			.'/'.self::segment('edit', $locale)
			.'/'.$uuid;
	}

	/**
	 * The expert portal — `/de/experte/profil` ([[08-accounts]]).
	 *
	 * The second of legacy's two role trees, and the reason there are two: what
	 * an expert sees is the courses they *teach*, with a participant list and a
	 * message composer on each, where a student sees the courses they bought.
	 * Four accounts hold both roles and need both screens at once, so the role
	 * in the path is what separates them ([[SiteUrl::studentPortal]]).
	 */
	public static function expertPortal(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('expert', $locale).'/'.self::segment('profile', $locale);
	}

	/**
	 * The expert's profile form — `/de/experte/profil/bearbeiten`.
	 *
	 * A screen rather than a panel, for the reason the student's is
	 * ([[SiteUrl::studentProfileEdit]]) — minus the one that forced it. There
	 * are no invoice addresses inside an expert's form, so nothing here links
	 * out of it; it is a screen because the two forms should not disagree about
	 * what they are, and because a toggle is state that the back button cannot
	 * see.
	 */
	public static function expertProfileEdit(?string $locale = null): string
	{
		return self::expertPortal($locale).'/'.self::segment('edit', $locale);
	}

	/**
	 * One course an expert teaches —
	 * `/de/experte/profil/kurs/veranstaltung/{uuid}`.
	 *
	 * The event's uuid, as on the student's side, and the same path under a
	 * different root.
	 */
	public static function expertEvent(string $uuid, ?string $locale = null): string
	{
		return self::expertPortal($locale)
			.'/'.self::segment('course', $locale)
			.'/'.self::segment('event', $locale)
			.'/'.$uuid;
	}

	/**
	 * The message composer —
	 * `/de/experte/profil/kurs/veranstaltung/{uuid}/message`.
	 *
	 * **English segment inside the German path**, which is legacy's own and is
	 * the same wart `/de/checkout/basket` carries ([[SiteUrl::checkout]]).
	 */
	public static function expertEventMessage(string $uuid, ?string $locale = null): string
	{
		return self::expertEvent($uuid, $locale).'/'.self::segment('message', $locale);
	}

	/** *Dokumente hochladen* — `…/{uuid}/file-upload`, legacy's spelling. */
	public static function expertEventUpload(string $uuid, ?string $locale = null): string
	{
		return self::expertEvent($uuid, $locale).'/'.self::segment('upload', $locale);
	}

	/**
	 * Where the header's *Profil* points: the login for a guest, the customer
	 * portal for anyone signed in ([[08-accounts]], `12-customers.md`).
	 *
	 * Legacy's `MenuItemProfile` chose between three portals by role and a
	 * `selected-role` from its role-picker screen. Here an account with more
	 * than the customer area gets the header's menu of them instead
	 * ([[areasFor]]), so this link is only ever shown to a customer.
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

		// Every account is a customer (`12-customers.md`), so anyone signed in
		// has the customer portal. An account with another area as well gets
		// the header's menu of them rather than this link ([[areasFor]]).
		return self::customerPortal($locale);
	}

	/**
	 * Every area this person may use, for the header's profile menu — the
	 * rework's answer to legacy's role picker (`auth/roles.blade.php`), which
	 * asked after login and kept the answer in the session. The areas have their
	 * own URLs, so a menu needs no session and nobody is stopped at login
	 * (`12-customers.md`).
	 *
	 * Labelled as legacy's picker names the roles, *Kunde* for the customer
	 * (#42), and *Dashboard* for Admin, which is where it leads. In `profileFor()`'s order.
	 *
	 * @return list<array{key: string, label: string, href: string}>
	 */
	public static function areasFor(?User $user, ?string $locale = null): array
	{
		if ($user === null) {
			return [];
		}

		return array_values(array_filter([
			// Everyone: every account is a customer.
			['key' => 'customer', 'label' => 'Kunde', 'href' => self::customerPortal($locale)],
			$user->hasRole(Role::Expert) ? ['key' => 'expert', 'label' => 'Experte', 'href' => self::expertPortal($locale)] : null,
			$user->hasRole(Role::Admin) ? ['key' => 'admin', 'label' => 'Dashboard', 'href' => '/dashboard'] : null,
		]));
	}

	/** One Vorhaben — `/de/vorhaben/{slug}` ([[04-content]]). */
	public static function project(string $slug, ?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('project', $locale).'/'.$slug;
	}

	/** Where the homepage footer's newsletter form posts — `/de/newsletter`. */
	public static function newsletter(?string $locale = null): string
	{
		$locale ??= app()->getLocale();

		return '/'.$locale.'/'.self::segment('newsletter', $locale);
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
