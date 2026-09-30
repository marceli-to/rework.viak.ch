<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The dashboard's SPA shell, for admins only ([[07-dashboard]]).
 *
 * Legacy guards it with `role:admin`, which answers anyone else with a bare
 * 403. A student or expert who reaches `/dashboard` has almost always followed
 * an old bookmark or typed the URL, so they are sent to their own portal
 * instead — the same place the header's *Profil* icon takes them. `auth` in
 * front of this sends a guest to the login and back.
 */
class DashboardController extends Controller
{
	public function __invoke(Request $request): View|RedirectResponse
	{
		$user = $request->user();

		// An expert to the expert portal, as the login lands them; anyone else
		// to the customer portal, since every account is a customer.
		if (! $user->isAdmin()) {
			return redirect($user->hasRole(Role::Expert) ? SiteUrl::expertPortal() : SiteUrl::profileFor($user));
		}

		return view('dashboard');
	}
}
