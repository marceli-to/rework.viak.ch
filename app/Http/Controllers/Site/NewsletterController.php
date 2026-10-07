<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SubscribeNewsletterRequest;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The homepage footer's *Abonnieren* (legacy's `NewsletterSubscriberController`).
 *
 * **A stand-in: nothing reaches Mailchimp.** Legacy subscribes the address to
 * the client's live list and tags it `Deutsch` plus `MAILCHIMP_TAGS`. Whether
 * that integration exists at all in the rework is `Open-Questions.md` #13,
 * and a prototype does not write to a client's live list any more than it
 * posts to Run My Accounts ([[03-invoices]]). So the form is real, validated
 * and guarded as Kontakt's is, and a signup is written to the log until #13
 * is answered.
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

		Log::info('Newsletter signup (not sent to Mailchimp, Open-Questions #13)', $request->safe()->only(['firstname', 'name', 'email']));

		return $back->with('newsletter', 'sent');
	}
}
