<?php

declare(strict_types=1);

use App\Models\Course;

/** *07*, as legacy prints a course number, so the titles after it line up. */
it('pads a course number to two digits for display', function () {
	expect((new Course(['number' => 7]))->displayNumber())->toBe('07')
		->and((new Course(['number' => 23]))->displayNumber())->toBe('23')
		->and((new Course(['number' => 107]))->displayNumber())->toBe('107');
});
