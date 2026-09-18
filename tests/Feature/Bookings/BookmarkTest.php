<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * 17 rows in three years across the whole site, which is the budget: a pivot and
 * two methods ([[06-bookings]]).
 */
it('saves and forgets a course', function () {
	$user = User::factory()->create();
	$event = Event::factory()->create();

	$user->bookmarks()->attach($event);
	expect($user->hasBookmarked($event))->toBeTrue();

	$user->forgetBookmark($event);
	expect($user->fresh()->hasBookmarked($event))->toBeFalse();
});

it('cannot save the same course twice', function () {
	$user = User::factory()->create();
	$event = Event::factory()->create();

	$user->bookmarks()->attach($event);
	$user->bookmarks()->attach($event);
})->throws(UniqueConstraintViolationException::class);
