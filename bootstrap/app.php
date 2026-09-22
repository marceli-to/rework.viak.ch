<?php

use App\Exceptions\BasketPriceChanged;
use App\Exceptions\DiscountCodeNotRedeemable;
use App\Exceptions\SeatNotAvailable;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__.'/../routes/web.php',
		api: __DIR__.'/../routes/api.php',
		commands: __DIR__.'/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		/*
		 * **`auth:sanctum` cannot see a session without this.** Found from a
		 * browser on 2026-09-22, not from the tests ([[09-public-site]]).
		 *
		 * Laravel's slim skeleton does not register
		 * `EnsureFrontendRequestsAreStateful`, so the `api` group never ran it
		 * and `auth:sanctum` fell through to the **token** guard — which finds
		 * no bearer token on a browser request and answers 401. Every endpoint
		 * behind that guard was unreachable from a signed-in page: the basket,
		 * the bookings, the profile, the whole Vue dashboard.
		 *
		 * The tests could not catch it. `actingAs()` sets the guard directly
		 * and never goes near the middleware, so 251 passing booking tests said
		 * nothing about whether a browser could reach any of them. The first
		 * screen to make a real request was the basket.
		 */
		$middleware->statefulApi();

		// Legacy's `role:student`, which the whole checkout sits behind
		// ([[EnsureUserHasRole]]).
		$middleware->alias(['role' => EnsureUserHasRole::class]);
	})
	->withExceptions(function (Exceptions $exceptions): void {
		$exceptions->shouldRenderJsonWhen(
			fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
		);

		/*
		 * The three ways a checkout can legitimately fail ([[06-bookings]]).
		 *
		 * All of them are 422 rather than 500, because none is a bug: a code
		 * expired, the price moved while the basket sat open, or the last seat
		 * went. Each one carries a message the customer is meant to read —
		 * which is the whole point. Legacy's equivalents failed *silently*:
		 * `Discount::apply()` returned FALSE and became a 0 discount, and
		 * nothing re-checked the price or the seat at all.
		 *
		 * **And there are two callers now.** The API answers JSON; the
		 * checkout's summary step is a Blade form, and a form post that gets
		 * 422 JSON back shows the customer a page of braces. So each one is a
		 * redirect with the message in the error bag when the caller is not
		 * asking for JSON — which is what puts it in the toast on the summary
		 * page ([[09-public-site]]).
		 */
		$wantsJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

		$exceptions->render(fn (DiscountCodeNotRedeemable $e, Request $request) => $wantsJson($request)
			? response()->json([
				'message' => $e->getMessage(),
				'errors' => ['code' => [$e->getMessage()]],
			], 422)
			: back()->withErrors(['code' => $e->getMessage()]));

		$exceptions->render(fn (BasketPriceChanged $e, Request $request) => $wantsJson($request)
			? response()->json([
				'message' => $e->getMessage(),
				'shown' => $e->shown,
				'actual' => $e->actual,
			], 422)
			: back()->withErrors(['total_shown' => $e->getMessage()]));

		$exceptions->render(fn (SeatNotAvailable $e, Request $request) => $wantsJson($request)
			? response()->json([
				'message' => $e->getMessage(),
				'event' => $e->event->uuid,
			], 422)
			: back()->withErrors(['items' => $e->getMessage()]));
	})->create();
