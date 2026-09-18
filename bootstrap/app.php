<?php

use App\Exceptions\BasketPriceChanged;
use App\Exceptions\DiscountCodeNotRedeemable;
use App\Exceptions\SeatNotAvailable;
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
		//
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
		 */
		$exceptions->render(fn (DiscountCodeNotRedeemable $e) => response()->json([
			'message' => $e->getMessage(),
			'errors' => ['code' => [$e->getMessage()]],
		], 422));

		$exceptions->render(fn (BasketPriceChanged $e) => response()->json([
			'message' => $e->getMessage(),
			'shown' => $e->shown,
			'actual' => $e->actual,
		], 422));

		$exceptions->render(fn (SeatNotAvailable $e) => response()->json([
			'message' => $e->getMessage(),
			'event' => $e->event->uuid,
		], 422));
	})->create();
