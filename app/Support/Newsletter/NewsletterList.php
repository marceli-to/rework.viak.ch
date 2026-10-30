<?php

declare(strict_types=1);

namespace App\Support\Newsletter;

/**
 * The client's newsletter list ([[SyncNewsletter]], Open-Questions #13).
 *
 * Legacy's Mailchimp sync, kept as it was (client, 2026-10-30): the footer's
 * signup and every account whose *Newsletter abonnieren* box is ticked are
 * subscribed and tagged, an account that unticks it is unsubscribed.
 *
 * An interface on the accounting system's principle ([[AccountingSystem]]):
 * the list is VIAK's live audience and staging holds real customers, so
 * **nothing reaches it from anywhere but production.** Everywhere else gets
 * [[FakeNewsletterList]].
 */
interface NewsletterList
{
	/** Subscribe or update, then tag (legacy's `subscribeOrUpdate` plus tags). */
	public function subscribe(string $email, string $firstName, string $lastName): void;

	/** An address the list does not hold is not an error. */
	public function unsubscribe(string $email): void;
}
