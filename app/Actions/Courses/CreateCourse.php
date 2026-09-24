<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Support\CourseNumber;
use App\Support\Slug;

/**
 * Creates a course ([[02-courses-events]]). Slugs are derived per locale from
 * the title.
 *
 * The number is the admin's to type, as in legacy (Marcel, 2026-09-24), and
 * falls back to the next free one. Legacy's duplicates came from checking
 * uniqueness on create only; [[SaveCourseRequest]] checks it on every save,
 * against deleted courses too.
 *
 * @phpstan-type Translated array<string, string|null>
 */
class CreateCourse
{
	public function __construct(
		private readonly CourseNumber $numbers,
	) {}

	/** @param array<string, mixed> $attributes */
	public function execute(array $attributes): Course
	{
		// Typed in the dashboard, as in legacy; the next free one when not.
		$attributes['number'] ??= $this->numbers->next();
		$attributes['slug'] = Slug::forTitles($attributes['title']);

		return Course::create($attributes);
	}
}
