<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Support\Slug;

/**
 * Updates a course. The slug is deliberately *not* regenerated from a changed
 * title: published course URLs are indexed and linked from invoices and mails,
 * so renaming a course must not silently break them. Slugs change only when
 * explicitly posted.
 */
class UpdateCourse
{
	/** @param array<string, mixed> $attributes */
	public function execute(Course $course, array $attributes): Course
	{
		if (isset($attributes['slug'])) {
			$attributes['slug'] = Slug::forTitles($attributes['slug']);
		}

		$course->update($attributes);

		return $course->refresh();
	}
}
