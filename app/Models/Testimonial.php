<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

/**
 * A quote from a customer, entered by VIAK ([[04-content]]) — what replaces the
 * Elfsight Google reviews, decided in the 2026-09-23 review.
 */
class Testimonial extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;

	protected $fillable = ['quote', 'name', 'context', 'publish', 'order', 'subject_type', 'subject_id'];

	/** @var array<int, string> */
	public $translatable = ['quote', 'context'];

	protected function casts(): array
	{
		return [
			'publish' => 'boolean',
			'order' => 'integer',
		];
	}

	/**
	 * What it is about: a course, a software, or null for VIAK as a whole.
	 * Set on the testimonial, once; where it is *shown* is `placements()`.
	 */
	public function subject(): MorphTo
	{
		return $this->morphTo();
	}

	/**
	 * The subject as the form's select names it — `course:{uuid}`,
	 * `software:{uuid}`, or empty for *Allgemein*.
	 */
	public function subjectKey(): string
	{
		return match (true) {
			$this->subject instanceof Course => 'course:'.$this->subject->uuid,
			$this->subject instanceof Software => 'software:'.$this->subject->uuid,
			default => '',
		};
	}

	/** The hint a list or a picker shows beside the quote. */
	public function subjectLabel(): string
	{
		return match (true) {
			$this->subject instanceof Course => 'zu: '.$this->subject->number.' '.$this->subject->getTranslation('title', 'de'),
			$this->subject instanceof Software => 'zu: '.$this->subject->getTranslation('title', 'de'),
			default => 'allgemein',
		};
	}

	/** The courses it stands on ([[HasTestimonials]]). */
	public function courses(): MorphToMany
	{
		return $this->morphedByMany(Course::class, 'placeable', 'testimonial_placements')->withPivot('order');
	}

	/** The software pages it stands on. */
	public function software(): MorphToMany
	{
		return $this->morphedByMany(Software::class, 'placeable', 'testimonial_placements')->withPivot('order');
	}

	/**
	 * Every page it stands on, for *Verwendet auf* — courses by number and
	 * title, then software. The homepage joins this when it has a record.
	 *
	 * @return array<int, array{type: string, uuid: string, label: string}>
	 */
	public function placements(): array
	{
		return [
			...$this->courses->sortBy('number')->map(fn (Course $course) => [
				'type' => 'course',
				'uuid' => $course->uuid,
				'label' => $course->number.' '.$course->getTranslation('title', 'de'),
			])->values()->all(),
			...$this->software->map(fn (Software $software) => [
				'type' => 'software',
				'uuid' => $software->uuid,
				'label' => $software->getTranslation('title', 'de'),
			])->values()->all(),
		];
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order')->orderBy('id');
	}
}
