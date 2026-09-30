<?php

declare(strict_types=1);

use App\Support\LegacyTime;

/**
 * Legacy stored UTC; this app runs in Zurich (open question #40). Summer is two
 * hours ahead, winter one, and the switch falls on the last Sunday of March.
 */
it('shifts a summer moment two hours', function () {
	expect(LegacyTime::local('2025-07-01 14:45:00'))->toBe('2025-07-01 16:45:00');
});

it('shifts a winter moment one hour', function () {
	expect(LegacyTime::local('2025-01-15 23:30:00'))->toBe('2025-01-16 00:30:00');
});

it('changes over with daylight saving time', function () {
	expect(LegacyTime::local('2025-03-30 00:59:59'))->toBe('2025-03-30 01:59:59')
		->and(LegacyTime::local('2025-03-30 01:00:00'))->toBe('2025-03-30 03:00:00');
});

it('leaves a missing moment missing', function () {
	expect(LegacyTime::local(null))->toBeNull();
});
