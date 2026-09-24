<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\CourseVideo;

/**
 * The parts of the course form that are not the course's own columns — its
 * five taxonomies and its videos ([[07-dashboard]]).
 *
 * Both arrive whole and are made to match. The taxonomies by uuid, which is
 * all the API ever hands out. The videos as a list: a row with a uuid of this
 * course is updated, one without is created, one no longer listed is deleted,
 * and the list's order is the order the course page shows them in. Legacy
 * saved each video on its own button beside the form; here they save with it.
 */
class SaveCourseRelations
{
	/**
	 * @param  array<string, array<int, string>>  $taxonomies  relation => uuids
	 * @param  array<int, array{uuid?: ?string, title?: ?string, code: string, publish?: bool}>  $videos
	 */
	public function execute(Course $course, array $taxonomies, array $videos): Course
	{
		foreach ($taxonomies as $relation => $uuids) {
			$ids = $course->{$relation}()->getRelated()->newQuery()->whereIn('uuid', $uuids)->pluck('id');
			$course->{$relation}()->sync($ids);
		}

		$kept = [];

		foreach (array_values($videos) as $position => $video) {
			$row = $course->videos()->where('uuid', $video['uuid'] ?? '')->first() ?? new CourseVideo(['course_id' => $course->id]);

			$row->fill([
				'title' => ['de' => $video['title'] ?? null],
				'code' => $video['code'],
				'publish' => (bool) ($video['publish'] ?? true),
				'order' => $position + 1,
			])->save();

			$kept[] = $row->id;
		}

		$course->videos()->whereNotIn('id', $kept)->delete();

		return $course;
	}
}
