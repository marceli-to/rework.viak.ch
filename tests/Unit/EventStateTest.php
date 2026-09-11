<?php

declare(strict_types=1);

use App\Enums\EventState;

it('only accepts bookings while planned or confirmed', function (EventState $state, bool $expected) {
	expect($state->acceptsBookings())->toBe($expected);
})->with([
	[EventState::Planned, true],
	[EventState::Confirmed, true],
	[EventState::Cancelled, false],
	[EventState::Closed, false],
]);

it('maps each state to the column recording the transition', function () {
	expect(EventState::Confirmed->timestampColumn())->toBe('confirmed_at')
		->and(EventState::Cancelled->timestampColumn())->toBe('cancelled_at')
		->and(EventState::Closed->timestampColumn())->toBe('closed_at')
		->and(EventState::Planned->timestampColumn())->toBeNull();
});
