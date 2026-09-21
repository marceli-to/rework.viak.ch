<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Home;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Where a registration lands ([[08-accounts]]).
 *
 * The same rule as [[LoginResponse]], and `intended()` matters more here: the
 * commonest reason to register at all is that the checkout asked for an account.
 */
class RegisterResponse implements RegisterResponseContract
{
	public function toResponse($request): RedirectResponse
	{
		return redirect()->intended(Home::for($request->user()));
	}
}
