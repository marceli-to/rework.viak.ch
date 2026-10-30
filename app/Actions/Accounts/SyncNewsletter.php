<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\User;
use App\Support\Newsletter\NewsletterList;
use Throwable;

/**
 * An account's *Newsletter abonnieren* box, mirrored to the list
 * ([[NewsletterList]]). Called after every save of it: registration, the
 * portals' and the dashboard's profile, an admin creating or editing a person.
 *
 * Legacy synced on every save, and so unsubscribed anyone without the box
 * ticked whenever an admin corrected their street number, including somebody
 * who had signed up in the footer. Here only a save that changes something the
 * list holds goes out: the box, or the name or address of a subscriber. A new
 * account without the box sends nothing.
 *
 * **A failure is reported, never thrown**, as `spatie/laravel-newsletter`
 * swallowed it in legacy: a profile is not left unsaved because Mailchimp is
 * down, or because production is missing its key. The list is resolved inside
 * the `try` for that second reason.
 */
class SyncNewsletter
{
	public function execute(User $user): void
	{
		$box = $user->wasRecentlyCreated || $user->wasChanged('subscribe_newsletter');
		$details = $user->wasChanged(['email', 'first_name', 'last_name']);

		try {
			if ($user->subscribe_newsletter && ($box || $details)) {
				app(NewsletterList::class)->subscribe($user->email, (string) $user->first_name, (string) $user->last_name);
			} elseif (! $user->subscribe_newsletter && $box && ! $user->wasRecentlyCreated) {
				app(NewsletterList::class)->unsubscribe($user->email);
			}
		} catch (Throwable $e) {
			report($e);
		}
	}
}
