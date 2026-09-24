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
 * **The dashboard is for admins only** since 2026-09-24 ([[07-dashboard]]),
 * so an expert lands in the expert portal, which exists now. A student still
 * lands on the public site, as on legacy. An account holding Admin goes to the
 * dashboard whatever else it holds — the three real ones that hold all three
 * roles are staff first.
 */
class Home
{
	public static function for(?User $user): string
	{
		if ($user === null) {
			return SiteUrl::home();
		}

		return match (true) {
			$user->hasRole(Role::Admin) => route('dashboard'),
			$user->hasRole(Role::Expert) => SiteUrl::expertPortal(),
			default => SiteUrl::home(),
		};
	}
}
