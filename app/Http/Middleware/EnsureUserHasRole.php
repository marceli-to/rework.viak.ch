<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy's `role:student` ([[08-accounts]]).
 *
 * The checkout is a student's, on both sites: legacy wraps every step in
 * `role:student` inside a group that is already `auth:sanctum, verified`. An
 * admin who is not also a student gets a 403 on the basket, which sounds odd
 * until you notice that a booking belongs to a `user_id` and an admin has no
 * business making one for themselves.
 *
 * **The order matters, and legacy's did not.** `CheckRole` reads
 * `auth()->user() && ! hasAtLeastOneRole(…)`, so a **guest passes it** — it
 * leans entirely on `auth` running first, and the one route that forgot is the
 * account-takeover path in `Todo.md`. Here a request with no user is refused by
 * this middleware too, so the guarantee does not depend on the order of a
 * list.
 */
class EnsureUserHasRole
{
	public function handle(Request $request, Closure $next, string ...$roles): Response
	{
		$user = $request->user();

		if ($user === null) {
			abort(403);
		}

		$wanted = array_map(Role::from(...), $roles);

		if (! collect($wanted)->contains(fn (Role $role) => $user->hasRole($role))) {
			abort(403);
		}

		return $next($request);
	}
}
