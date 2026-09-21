<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Home;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Where a login lands ([[08-accounts]]).
 *
 * Fortify's own sends everyone to `config('fortify.home')`. This keeps
 * `intended()` — which is what carries a guest back to the checkout step they
 * were bounced off — and makes the fallback depend on who signed in rather than
 * on one path for all three roles. [[Home]] has the reasoning.
 */
class LoginResponse implements LoginResponseContract
{
	public function toResponse($request): RedirectResponse
	{
		return redirect()->intended(Home::for($request->user()));
	}
}
