<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Where a signed-in person belongs ([[08-accounts]]).
 *
 * Two places need the same answer and got different ones: Fortify's
 * `LoginResponse` reads `config('fortify.home')`, while the framework's `guest`
 * middleware — which is what bounces an already-signed-in visitor off `/login` —
 * ignores it entirely and hunts for **a route named `dashboard`**
 * (`RedirectIfAuthenticated::defaultRedirectUri()`). This app has one: the SPA
 * shell. So a student who was already logged in and opened the login page
 * landed in the admin dashboard, which then redirected to `/dashboard/termine`.
 *
 * The dashboard is for admins and experts. A student goes to the public site,
 * and will go to their portal once `08-accounts.md`'s screens exist.
 */
class Home
{
	public static function for(?User $user): string
	{
		if ($user === null) {
			return SiteUrl::home();
		}

		$staff = $user->hasRole(Role::Admin) || $user->hasRole(Role::Expert);

		return $staff ? route('dashboard') : SiteUrl::home();
	}
}
