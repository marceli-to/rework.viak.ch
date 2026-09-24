<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\BasketController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Public reads are open; writes sit behind the session guard and are gated
 * further by policy. The SPA and the public site consume the same endpoints —
 * never a parallel write path. See [[02-courses-events]].
 */

Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
	Route::post('events', [EventController::class, 'store']);
	Route::put('events/{event}', [EventController::class, 'update']);
	Route::patch('events/{event}/state', [EventController::class, 'setState']);
	Route::delete('events/{event}', [EventController::class, 'destroy']);

	/*
	 * Basket and checkout ([[06-bookings]]).
	 *
	 * `basket/price` is a POST because it sends the whole selection and gets a
	 * priced answer — it reads as a query but it is not addressable, and there
	 * is no basket on the server to GET. Legacy kept one in the session, which
	 * is why a completed checkout left nothing behind.
	 */
	Route::post('basket/price', [BasketController::class, 'price']);
	Route::post('checkout', [BasketController::class, 'store']);

	Route::get('bookings', [BookingController::class, 'index']);
	Route::get('bookings/{booking}', [BookingController::class, 'show']);
	Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
	Route::patch('bookings/{booking}/rental', [BookingController::class, 'setRental']);

	/*
	 * Course notes ([[08-accounts]]). Authorised against the event, which is
	 * the check legacy's role-only gate skipped.
	 */
	Route::get('events/{event}/messages', [MessageController::class, 'index']);
	Route::post('events/{event}/messages', [MessageController::class, 'store']);
	Route::delete('messages/{message}', [MessageController::class, 'destroy']);

	/*
	 * The account, for all three roles ([[08-accounts]]). Legacy had three
	 * controllers differing only in which fields they validated, each with its
	 * own copy of the same unconfirmed email change.
	 */
	Route::get('profile', [ProfileController::class, 'show']);
	Route::put('profile', [ProfileController::class, 'update']);
	Route::get('profile/documents', [ProfileController::class, 'documents']);

	Route::get('addresses', [AddressController::class, 'index']);
	Route::post('addresses', [AddressController::class, 'store']);
	Route::put('addresses/{address}', [AddressController::class, 'update']);
	Route::delete('addresses/{address}', [AddressController::class, 'destroy']);

	Route::get('bookmarks', [BookmarkController::class, 'index']);
	Route::put('bookmarks/{event}', [BookmarkController::class, 'store']);
	Route::delete('bookmarks/{event}', [BookmarkController::class, 'destroy']);
});

/*
 * The dashboard's own endpoints ([[07-dashboard]]). Everything only an admin
 * uses lives here, behind the role on the whole group — the rule Marcel and I
 * settled on 2026-09-24, where legacy drew the same line with `/api/dashboard`.
 * The public and portal endpoints above stay where they are; the course and
 * event writes move in here as their forms are built.
 */
Route::middleware(['auth:sanctum', 'role:admin'])
	->prefix('admin')
	->group(function (): void {
		Route::get('courses', [Admin\CourseController::class, 'index']);
		Route::post('courses/order', [Admin\CourseController::class, 'order']);
		Route::get('courses/options', [Admin\CourseController::class, 'options']);
		Route::post('courses', [Admin\CourseController::class, 'store']);
		Route::get('courses/{course}', [Admin\CourseController::class, 'show']);
		Route::put('courses/{course}', [Admin\CourseController::class, 'update']);
		Route::delete('courses/{course}', [Admin\CourseController::class, 'destroy']);

		Route::get('courses/{course}/media', [Admin\MediaController::class, 'index']);
		Route::post('courses/{course}/media', [Admin\MediaController::class, 'store']);
		Route::patch('courses/{course}/media/order', [Admin\MediaController::class, 'order']);
		Route::put('media/{media:uuid}', [Admin\MediaController::class, 'update']);
		Route::patch('media/{media:uuid}/role', [Admin\MediaController::class, 'role']);
		Route::patch('media/{media:uuid}/crop', [Admin\MediaController::class, 'crop']);
		Route::delete('media/{media:uuid}', [Admin\MediaController::class, 'destroy']);

		Route::patch('events/{event}/state', [Admin\EventController::class, 'setState']);
	});
