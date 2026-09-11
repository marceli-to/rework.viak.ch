<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Course;

/**
 * Course numbers are sequential and shown to students on invoices, so they must
 * not be reused. Soft-deleted courses still hold their number.
 */
final class CourseNumber
{
	public function next(): int
	{
		return (int) Course::withTrashed()->max('number') + 1;
	}
}
