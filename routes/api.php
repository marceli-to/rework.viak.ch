<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\BasketController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Models\User;
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
 * event writes moved in here with their forms.
 */
// `{expert}` is a person holding that role, by uuid, and nobody else. `{customer}`
// is any account: every account is a customer (`12-customers.md`).
Route::bind('expert', fn (string $uuid) => User::query()->withRole(Role::Expert)->where('uuid', $uuid)->firstOrFail());
Route::bind('customer', fn (string $uuid) => User::query()->where('uuid', $uuid)->firstOrFail());

Route::middleware(['auth:sanctum', 'role:admin'])
	->prefix('admin')
	->group(function (): void {
		Route::get('forms/{form}', [Admin\FormController::class, 'show']);

		Route::get('courses', [Admin\CourseController::class, 'index']);
		Route::post('courses/order', [Admin\CourseController::class, 'order']);
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

		Route::get('testimonials', [Admin\TestimonialController::class, 'index']);
		Route::post('testimonials', [Admin\TestimonialController::class, 'store']);
		Route::get('testimonials/{testimonial}', [Admin\TestimonialController::class, 'show']);
		Route::put('testimonials/{testimonial}', [Admin\TestimonialController::class, 'update']);
		Route::delete('testimonials/{testimonial}', [Admin\TestimonialController::class, 'destroy']);

		// A fixed page's testimonials, picked in the dashboard ([[Page]]).
		Route::get('pages/{page}/testimonials', [Admin\PageTestimonialController::class, 'show']);
		Route::put('pages/{page}/testimonials', [Admin\PageTestimonialController::class, 'update']);
		// A fixed page's images, by its key: the homepage's slider ([[Page]]).
		Route::get('pages/{page}/media', [Admin\MediaController::class, 'pageIndex']);
		Route::post('pages/{page}/media', [Admin\MediaController::class, 'pageStore']);
		Route::patch('pages/{page}/media/order', [Admin\MediaController::class, 'pageOrder']);

		// The Vorhaben, the homepage's tiles and a page each ([[04-content]]).
		Route::get('projects', [Admin\ProjectController::class, 'index']);
		Route::post('projects/order', [Admin\ProjectController::class, 'order']);
		Route::post('projects', [Admin\ProjectController::class, 'store']);
		Route::get('projects/{project}', [Admin\ProjectController::class, 'show']);
		Route::put('projects/{project}', [Admin\ProjectController::class, 'update']);
		Route::delete('projects/{project}', [Admin\ProjectController::class, 'destroy']);

		Route::get('team-members', [Admin\TeamMemberController::class, 'index']);
		Route::post('team-members/order', [Admin\TeamMemberController::class, 'order']);
		Route::post('team-members', [Admin\TeamMemberController::class, 'store']);
		Route::get('team-members/{teamMember}', [Admin\TeamMemberController::class, 'show']);
		Route::put('team-members/{teamMember}', [Admin\TeamMemberController::class, 'update']);
		Route::delete('team-members/{teamMember}', [Admin\TeamMemberController::class, 'destroy']);
		Route::get('team-members/{teamMember}/media', [Admin\MediaController::class, 'teamMemberIndex']);
		Route::post('team-members/{teamMember}/media', [Admin\MediaController::class, 'teamMemberStore']);
		Route::patch('team-members/{teamMember}/media/order', [Admin\MediaController::class, 'teamMemberOrder']);

		Route::get('experts', [Admin\ExpertController::class, 'index']);
		Route::post('experts/order', [Admin\ExpertController::class, 'order']);
		Route::post('experts', [Admin\ExpertController::class, 'store']);
		Route::get('experts/{expert}', [Admin\ExpertController::class, 'show']);
		Route::put('experts/{expert}', [Admin\ExpertController::class, 'update']);
		Route::delete('experts/{expert}', [Admin\ExpertController::class, 'destroy']);
		Route::get('experts/{expert}/media', [Admin\MediaController::class, 'expertIndex']);
		Route::post('experts/{expert}/media', [Admin\MediaController::class, 'expertStore']);
		Route::patch('experts/{expert}/media/order', [Admin\MediaController::class, 'expertOrder']);

		Route::get('customers', [Admin\CustomerController::class, 'index']);
		Route::post('customers', [Admin\CustomerController::class, 'store']);
		Route::get('customers/{customer}', [Admin\CustomerController::class, 'show']);
		Route::put('customers/{customer}', [Admin\CustomerController::class, 'update']);
		Route::patch('customers/{customer}/state', [Admin\CustomerController::class, 'state']);
		Route::get('customers/{customer}/page', [Admin\CustomerPageController::class, 'show']);
		Route::patch('bookings/{booking}/cancel', [Admin\CustomerPageController::class, 'cancel']);

		Route::get('discount-codes', [Admin\DiscountCodeController::class, 'index']);
		Route::post('discount-codes', [Admin\DiscountCodeController::class, 'store']);
		Route::get('discount-codes/{discountCode}', [Admin\DiscountCodeController::class, 'show']);
		Route::put('discount-codes/{discountCode}', [Admin\DiscountCodeController::class, 'update']);
		Route::delete('discount-codes/{discountCode}', [Admin\DiscountCodeController::class, 'destroy']);

		Route::get('invoices', [Admin\InvoiceController::class, 'index']);
		Route::get('invoices/{invoice}', [Admin\InvoiceController::class, 'show']);
		Route::put('invoices/{invoice}', [Admin\InvoiceController::class, 'update']);
		Route::get('exports/courses', [Admin\ExportController::class, 'courses']);

		Route::get('profile', [Admin\ProfileController::class, 'show']);
		Route::put('profile', [Admin\ProfileController::class, 'update']);

		Route::get('settings', [Admin\SettingController::class, 'index']);
		Route::post('settings/{kind}', [Admin\SettingController::class, 'store']);
		Route::get('settings/{kind}/{uuid}', [Admin\SettingController::class, 'show']);
		Route::put('settings/{kind}/{uuid}', [Admin\SettingController::class, 'update']);
		Route::delete('settings/{kind}/{uuid}', [Admin\SettingController::class, 'destroy']);

		Route::post('courses/{course}/events', [Admin\EventController::class, 'store']);
		Route::get('events/{event}', [Admin\EventController::class, 'show']);
		Route::put('events/{event}', [Admin\EventController::class, 'update']);
		Route::patch('events/{event}/state', [Admin\EventController::class, 'setState']);
		Route::get('events/{event}/page', [Admin\EventPageController::class, 'show']);
		Route::post('events/{event}/close', [Admin\EventPageController::class, 'close']);
		Route::post('events/{event}/bookings', [Admin\EventPageController::class, 'book']);
		Route::post('events/{event}/bookings/{booking:uuid}/confirm', [Admin\EventPageController::class, 'confirm']);
		Route::get('events/{event}/participants', [Admin\EventPageController::class, 'participants']);
		Route::post('events/{event}/messages', [Admin\EventPageController::class, 'message']);
		Route::post('events/{event}/files', [Admin\EventPageController::class, 'upload']);
		Route::delete('events/{event}/files/{media:uuid}', [Admin\EventPageController::class, 'removeFile']);
		Route::delete('events/{event}', [Admin\EventController::class, 'destroy']);
	});
