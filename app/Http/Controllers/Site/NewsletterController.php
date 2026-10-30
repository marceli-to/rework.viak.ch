<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SubscribeNewsletterRequest;
use App\Support\Newsletter\NewsletterList;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * The homepage footer's *Abonnieren* (legacy's `NewsletterSubscriberController`).
 *
 * As legacy: the address is subscribed to the client's Mailchimp list and
 * tagged `Deutsch` plus `MAILCHIMP_TAGS` ([[NewsletterList]], #13). In
 * production only; everywhere else the fake logs it. Validated and guarded as
 * Kontakt's form is.
 *
 * A Mailchimp failure is reported and the visitor still thanked, as legacy's
 * `spatie/laravel-newsletter` swallowed it.
 */
class NewsletterController extends Controller
{
	private const PER_HOUR = 5;

	public function store(SubscribeNewsletterRequest $request): RedirectResponse
	{
		$back = redirect(SiteUrl::home().'#newsletter');

		// A filled honeypot is a bot: tell it it worked, and do nothing.
		if (filled($request->input('website'))) {
			return $back->with('newsletter', 'sent');
		}

		$key = 'newsletter:'.$request->ip();

		if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
			return $back->withInput()->withErrors(['email' => 'Zu viele Anmeldungen in kurzer Zeit. Bitte versuche es später nochmals.']);
		}

		RateLimiter::hit($key, 3600);

		try {
			app(NewsletterList::class)->subscribe($request->string('email')->value(), $request->string('firstname')->value(), $request->string('name')->value());
		} catch (Throwable $e) {
			report($e);
		}

		return $back->with('newsletter', 'sent');
	}
}
