<?php

declare(strict_types=1);

use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingCancellationReason;
use App\Enums\ParticipantThreshold;
use App\Events\BookingMade;
use App\Events\ParticipantThresholdCrossed;
use App\Listeners\NotifyParticipantThreshold;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Facades\Event as Events;

/**
 * Legacy notified on `==` and nothing else, so any step that skipped the value
 * lost the notification for good ([[ParticipantThreshold]]).
 */
beforeEach(function () {
	$this->listener = app(NotifyParticipantThreshold::class);
	$this->event = Event::factory()->create(['min_participants' => 2, 'max_participants' => 3]);
});

function seatsOn(Event $event, int $count): void
{
	Booking::factory()->for($event)->count($count)->create();
}

function evaluate(Event $event): void
{
	app(NotifyParticipantThreshold::class)->handle(
		new BookingMade(Booking::factory()->for($event)->make())
	);
}

it('records the band on first evaluation without announcing it', function () {
	Events::fake([ParticipantThresholdCrossed::class]);
	seatsOn($this->event, 2);

	evaluate($this->event);

	expect($this->event->refresh()->participant_threshold)->toBe(ParticipantThreshold::Viable);
	Events::assertNotDispatched(ParticipantThresholdCrossed::class);
});

/**
 * The regression. Two bookings landing in one cycle jump the count from one
 * below the minimum to one above it — legacy's `count == min` never matched and
 * the course was never announced as viable.
 */
it('notifies when the count jumps straight over the minimum', function () {
	Events::fake([ParticipantThresholdCrossed::class]);
	$this->event->forceFill(['participant_threshold' => ParticipantThreshold::BelowMinimum])->save();

	seatsOn($this->event, 2);
	evaluate($this->event);

	Events::assertDispatched(
		ParticipantThresholdCrossed::class,
		fn (ParticipantThresholdCrossed $e) => $e->becameViable()
	);
});

it('notifies when the count jumps straight over the maximum', function () {
	Events::fake([ParticipantThresholdCrossed::class]);
	$this->event->forceFill(['participant_threshold' => ParticipantThreshold::BelowMinimum])->save();

	seatsOn($this->event, 4);
	evaluate($this->event);

	Events::assertDispatched(
		ParticipantThresholdCrossed::class,
		fn (ParticipantThresholdCrossed $e) => $e->becameFull()
	);
});

it('says nothing when the band has not changed', function () {
	seatsOn($this->event, 2);
	evaluate($this->event);

	Events::fake([ParticipantThresholdCrossed::class]);
	evaluate($this->event);

	Events::assertNotDispatched(ParticipantThresholdCrossed::class);
});

/** A cancellation that drops a course back below its minimum is a decision. */
it('notifies when a cancellation drops the course below its minimum', function () {
	$bookings = Booking::factory()->for($this->event)->count(2)->create();
	evaluate($this->event);

	Events::fake([ParticipantThresholdCrossed::class]);
	app(CancelBooking::class)->execute($bookings->first(), BookingCancellationReason::Student);

	Events::assertDispatched(
		ParticipantThresholdCrossed::class,
		fn (ParticipantThresholdCrossed $e) => $e->fellBelowMinimum()
	);
});

it('counts only seats that are still held', function () {
	Booking::factory()->for($this->event)->count(2)->create();
	Booking::factory()->for($this->event)->cancelled()->count(5)->create();

	expect($this->event->seatsTaken())->toBe(2)
		->and($this->event->currentThreshold())->toBe(ParticipantThreshold::Viable)
		->and($this->event->isFull())->toBeFalse();
});
