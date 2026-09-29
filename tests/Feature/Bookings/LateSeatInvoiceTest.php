<?php

declare(strict_types=1);

use App\Actions\Bookings\CompleteCheckout;
use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Bookings\PriceBasket;
use App\Enums\EventState;
use App\Events\BookingMade;
use App\Models\Course;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Event as Events;

/**
 * **A seat sold on a course already confirmed is billed at once** ([[03-invoices]]).
 * Its confirmation has passed, so nothing else would ever raise the invoice.
 * Found 2026-09-29 while building the mails: `CompleteCheckout` said it did
 * this and did not, and neither did the admin's booking.
 */
function courseEvent(EventState $state): Event
{
	return Event::factory()->for(Course::factory()->create(['fee' => '890.00']))->create(['state' => $state]);
}

function invoicesOf(User $user): int
{
	return Invoice::query()->where('user_id', $user->id)->count();
}

it('bills a seat bought at checkout on a confirmed course', function () {
	$user = User::factory()->create();

	app(CompleteCheckout::class)->execute($user, app(PriceBasket::class)->execute([['event' => courseEvent(EventState::Confirmed)]]));

	expect(invoicesOf($user))->toBe(1);
});

it('does not bill a seat on a course that is not confirmed yet', function () {
	$user = User::factory()->create();

	app(CompleteCheckout::class)->execute($user, app(PriceBasket::class)->execute([['event' => courseEvent(EventState::Planned)]]));

	expect(invoicesOf($user))->toBe(0);
});

it('bills a seat an admin books on a confirmed course', function () {
	$user = User::factory()->create();

	app(CreateBookingForUser::class)->execute(courseEvent(EventState::Confirmed), $user);

	expect(invoicesOf($user))->toBe(1);
});

it('has the invoice before anyone hears of the booking', function () {
	$user = User::factory()->create();
	$seen = null;
	Events::listen(BookingMade::class, function () use ($user, &$seen) {
		$seen = invoicesOf($user);
	});

	app(CompleteCheckout::class)->execute($user, app(PriceBasket::class)->execute([['event' => courseEvent(EventState::Confirmed)]]));

	expect($seen)->toBe(1);
});
