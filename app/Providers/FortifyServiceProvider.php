<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Accounts\RegisterUser;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Support\Home;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

/**
 * Fortify's screens ([[08-accounts]]).
 *
 * Fortify ships the routes and the logic and **no views at all** — until this
 * provider binds them, `GET /login` is a 500. Which is what it was: the package
 * was installed with chunk 08 and the screens were left to the frontend phase.
 *
 * The views are Blade under `site/auth/`, on the public layout, because that is
 * where they are on the live site — login is not part of the dashboard SPA.
 */
class FortifyServiceProvider extends ServiceProvider
{
	public function boot(): void
	{
		Fortify::loginView(fn () => view('site.auth.login'));
		Fortify::requestPasswordResetLinkView(fn () => view('site.auth.forgot-password'));
		Fortify::resetPasswordView(fn (Request $request) => view('site.auth.reset-password', ['request' => $request]));
		Fortify::verifyEmailView(fn () => view('site.auth.verify-email'));
		Fortify::registerView(fn () => view('site.auth.register'));

		Fortify::createUsersUsing(RegisterUser::class);

		$this->app->singleton(LoginResponseContract::class, LoginResponse::class);
		$this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

		/*
		 * **The `guest` middleware ignores `fortify.home`.** It looks for a route
		 * named `dashboard`, and this app has one — the SPA shell — so an
		 * already-signed-in student who opened `/login` was redirected into the
		 * admin dashboard. [[Home]] decides it by role instead.
		 */
		RedirectIfAuthenticated::redirectUsing(fn (Request $request) => Home::for($request->user()));

		/*
		 * Legacy has no rate limiting on login at all. Fortify's default is five
		 * a minute per email **and** IP, which is the one place this rebuild
		 * takes a default over parity: a login form without it is a password
		 * spray away from an account, and `08-accounts.md` already has one
		 * account-takeover finding on the live site.
		 */
		RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
			->by(Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip())));

	}
}
