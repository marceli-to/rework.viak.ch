<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Requests\Admin\SaveCourseRequest;
use App\Models\Course;
use App\Support\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course as the dashboard's form holds it ([[07-dashboard]]) — **the same
 * shape [[SaveCourseRequest]] takes back**, so the form sends what it was
 * given. German strings, taxonomy uuids, three facts, the videos inline.
 *
 * Plus three things the form reads and never sends: the number, which the
 * server assigns; the public URL; and whether any date of it has bookings,
 * which is what decides if it may be deleted.
 *
 * @mixin Course
 */
class CourseFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$de = fn (string $field) => $this->getTranslation($field, 'de', false) ?: '';
		$facts = array_values($this->facts ?? []);

		$form = [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'url' => SiteUrl::course($this->getTranslation('slug', 'de'), 'de'),
			'has_bookings' => $this->events()->whereHas('bookings')->exists(),

			'title' => $de('title'),
			'subtitle' => $de('subtitle'),
			'fee' => (string) $this->fee,
			'online' => $this->online,
			'publish' => $this->publish,
		];

		foreach (SaveCourseRequest::RICH as $field) {
			$form[$field] = $de($field);
		}

		$form['facts'] = collect(range(0, 2))
			->map(fn (int $index) => is_array($facts[$index] ?? null) ? ($facts[$index]['de'] ?? '') : (string) ($facts[$index] ?? ''))
			->all();

		foreach (array_keys(SaveCourseRequest::TAXONOMIES) as $relation) {
			$form[$relation] = $this->{$relation}->pluck('uuid')->all();
		}

		$form['videos'] = $this->videos->map(fn ($video) => [
			'uuid' => $video->uuid,
			'title' => $video->getTranslation('title', 'de', false) ?: '',
			'code' => $video->code,
			'publish' => $video->publish,
		])->all();

		$form['seo_description'] = $de('seo_description');
		$form['seo_tags'] = $de('seo_tags');

		return $form;
	}
}
