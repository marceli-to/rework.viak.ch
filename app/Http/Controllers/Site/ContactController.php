<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SendContactMessageRequest;
use App\Mail\ContactMessage;
use App\Mail\ContactMessageConfirmation;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The Kontakt form ([[04-content]]), answering `Open-Questions.md` #24 (Marcel,
 * 2026-10-06): the message goes to the office, `config('mail.admin')`, with
 * the sender as its Reply-To; the sender gets a confirmation; **nothing is
 * stored**, so the mail is the record.
 *
 * Spam is kept out without a third-party captcha, which would need a consent
 * question: a honeypot field, and five messages an hour from one address.
 * The confirmation is why the limit matters — it goes to whatever address was
 * typed, so the form must not become a way to mail strangers in bulk.
 */
class ContactController extends Controller
{
	private const PER_HOUR = 5;

	public function store(SendContactMessageRequest $request): RedirectResponse
	{
		$back = redirect(SiteUrl::contact().'#nachricht');

		// A filled honeypot is a bot: tell it it worked, and send nothing.
		if (filled($request->input('website'))) {
			return $back->with('contact', 'sent');
		}

		$key = 'contact:'.$request->ip();

		if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
			return $back->withInput()->withErrors(['message' => 'Zu viele Nachrichten in kurzer Zeit. Bitte versuche es später nochmals.']);
		}

		RateLimiter::hit($key, 3600);

		['name' => $name, 'email' => $email, 'message' => $text] = $request->validated();

		Mail::to(config('mail.admin'))->send(new ContactMessage($name, $email, $text));
		Mail::to($email)->send(new ContactMessageConfirmation($name, $email, $text));

		return $back->with('contact', 'sent');
	}
}
