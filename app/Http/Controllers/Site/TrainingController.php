<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SendTrainingEnquiryRequest;
use App\Mail\TrainingEnquiry;
use App\Models\Page;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Firmenschulung (`04-content.md`, `Open-Questions.md` #25): legacy's
 * *Individualschulungen* copy, an enquiry form and the testimonials the
 * dashboard picked for it ([[Page]]).
 *
 * The enquiry takes Kontakt's answer to #24 ([[ContactController]]): to the
 * office with the sender as Reply-To, nothing stored, no mail to the sender;
 * Turnstile, a honeypot and five an hour from one address.
 */
class TrainingController extends Controller
{
	private const PER_HOUR = 5;

	public function show(): View
	{
		return view('site.training.index', [
			'testimonials' => Page::for('firmenschulung')->testimonials()->published()->get(),
		]);
	}

	public function store(SendTrainingEnquiryRequest $request): RedirectResponse
	{
		$back = redirect(SiteUrl::training().'#anfrage');

		// A filled honeypot is a bot: tell it it worked, and send nothing.
		if (filled($request->input('website'))) {
			return $back->with('training', 'sent');
		}

		$key = 'training:'.$request->ip();

		if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
			return $back->withInput()->withErrors(['message' => 'Zu viele Anfragen in kurzer Zeit. Bitte versuche es später nochmals.']);
		}

		RateLimiter::hit($key, 3600);

		$data = $request->validated();

		Mail::to(config('mail.admin'))->send(new TrainingEnquiry($data['company'], $data['name'], $data['email'], $data['phone'] ?? null, $data['message']));

		return $back->with('training', 'sent');
	}
}
