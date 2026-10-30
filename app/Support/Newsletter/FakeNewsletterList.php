<?php

declare(strict_types=1);

namespace App\Support\Newsletter;

use Illuminate\Support\Facades\Log;

/**
 * The newsletter list everywhere except production ([[NewsletterList]]).
 * Records what would have been sent, for tests and the log, and sends nothing.
 */
class FakeNewsletterList implements NewsletterList
{
	/** @var array<int, string> */
	public array $subscribed = [];

	/** @var array<int, string> */
	public array $unsubscribed = [];

	public function subscribe(string $email, string $firstName, string $lastName): void
	{
		$this->subscribed[] = $email;

		Log::info('Newsletter (not sent to Mailchimp): subscribe '.$email);
	}

	public function unsubscribe(string $email): void
	{
		$this->unsubscribed[] = $email;

		Log::info('Newsletter (not sent to Mailchimp): unsubscribe '.$email);
	}
}
