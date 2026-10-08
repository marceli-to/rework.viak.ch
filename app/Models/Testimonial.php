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
	 * Set on the testimonial, once. A course or software subject also *shows*
	 * it, on that page ([[Course::testimonialsAbout]],
	 * [[Software::testimonialsAbout]]); everywhere else it is shown is
	 * `placements()`.
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

	/** What it is about, for the dashboard list's own column. */
	public function subjectLabel(): string
	{
		return match (true) {
			$this->subject instanceof Course => $this->subject->number.' '.$this->subject->getTranslation('title', 'de'),
			$this->subject instanceof Software => $this->subject->getTranslation('title', 'de'),
			default => 'Allgemein',
		};
	}

	/** The courses it stands on ([[HasTestimonials]]). */
	public function courses(): MorphToMany
	{
		return $this->morphedByMany(Course::class, 'placeable', 'testimonial_placements')->withPivot('order');
	}

	/** The fixed pages it stands on — Firmenschulung ([[Page]]). */
	public function pages(): MorphToMany
	{
		return $this->morphedByMany(Page::class, 'placeable', 'testimonial_placements')->withPivot('order');
	}

	/** The software pages it stands on. */
	public function software(): MorphToMany
	{
		return $this->morphedByMany(Software::class, 'placeable', 'testimonial_placements')->withPivot('order');
	}

	/**
	 * Every page it stands on, for *Verwendet auf* — courses by number and
	 * title, then software, then the fixed pages ([[Page]]).
	 *
	 * @return array<int, array{type: string, uuid: string, label: string}>
	 */
	public function placements(): array
	{
		// A published course shows the testimonials about it without being
		// picked ([[Course::testimonialsAbout]]), so its page counts as one.
		$courses = $this->courses;
		if ($this->subject instanceof Course && $this->subject->publish && ! $courses->contains($this->subject)) {
			$courses = $courses->concat([$this->subject]);
		}

		// The same for a software page ([[Software::testimonialsAbout]]).
		$software = $this->software;
		if ($this->subject instanceof Software && $this->subject->publish && ! $software->contains($this->subject)) {
			$software = $software->concat([$this->subject]);
		}

		return [
			...$courses->sortBy('number')->map(fn (Course $course) => [
				'type' => 'course',
				'uuid' => $course->uuid,
				'label' => $course->number.' '.$course->getTranslation('title', 'de'),
			])->values()->all(),
			...$software->map(fn (Software $software) => [
				'type' => 'software',
				'uuid' => $software->uuid,
				'label' => $software->getTranslation('title', 'de'),
			])->values()->all(),
			...$this->pages->map(fn (Page $page) => [
				'type' => 'page',
				'uuid' => $page->uuid,
				'label' => $page->label(),
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
