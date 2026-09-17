<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Support\CourseNumber;
use App\Support\Slug;

/**
 * Creates a course ([[02-courses-events]]). The course number is assigned here
 * rather than by the client — legacy let the form post it, which is how you end
 * up with duplicates. Slugs are derived per locale from the title.
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
		$attributes['number'] = $this->numbers->next();
		$attributes['slug'] = Slug::forTitles($attributes['title']);

		return Course::create($attributes);
	}
}
