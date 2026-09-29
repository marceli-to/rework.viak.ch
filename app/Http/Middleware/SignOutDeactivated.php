<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account that was deactivated while it was signed
 * in (#16, [[07-dashboard]]). The login refuses a deactivated account
 * ([[FortifyServiceProvider]]); this is for the session that was already
 * open, so deactivating takes effect at once and not whenever it expires.
 */
class SignOutDeactivated
{
	public const MESSAGE = 'Dieses Konto ist deaktiviert. Bitte melde dich bei uns, wenn das ein Irrtum ist.';

	public function handle(Request $request, Closure $next): Response
	{
		if (! $request->user()?->isDeactivated()) {
			return $next($request);
		}

		Auth::guard('web')->logout();

		if ($request->hasSession()) {
			$request->session()->invalidate();
			$request->session()->regenerateToken();
		}

		return $request->expectsJson() || $request->is('api/*')
			? response()->json(['message' => self::MESSAGE], 401)
			: redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
	}
}
