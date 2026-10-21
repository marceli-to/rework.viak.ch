<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Event;

/** `port:check-rentals` ([[CheckRentals]], `.rework/Deploy.md`). */
it('fails on an upcoming date with more laptops rented than the room has', function () {
	$event = Event::factory()->create(['date' => now()->addMonth(), 'rentals_available' => 1]);
	Booking::factory()->count(2)->for($event)->create(['has_rental' => true]);

	$this->artisan('port:check-rentals')->assertFailed();
});

it('passes when the count holds, a cancelled booking freeing its laptop, and ignores past dates', function () {
	$event = Event::factory()->create(['date' => now()->addMonth(), 'rentals_available' => 1]);
	Booking::factory()->for($event)->create(['has_rental' => true]);
	Booking::factory()->for($event)->create(['has_rental' => true, 'cancelled_at' => now()]);

	$past = Event::factory()->create(['date' => now()->subMonth(), 'rentals_available' => 0]);
	Booking::factory()->for($past)->create(['has_rental' => true]);

	$this->artisan('port:check-rentals')->assertSuccessful();
});
