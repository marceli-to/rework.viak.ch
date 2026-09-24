<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * A page that shows testimonials — a course, a software, later the homepage
 * ([[Testimonial]], `testimonial_placements`). It picks them and orders them;
 * the testimonial only knows where it has been placed.
 */
trait HasTestimonials
{
	public static function bootHasTestimonials(): void
	{
		// No foreign key can reach a morph, so a page that is really gone
		// takes its placements with it. A soft delete keeps them, as it keeps
		// everything else, and the relation already hides a trashed page.
		static::forceDeleted(fn ($page) => $page->testimonials()->detach());
	}

	/** In this page's own order. */
	public function testimonials(): MorphToMany
	{
		return $this->morphToMany(Testimonial::class, 'placeable', 'testimonial_placements')
			->withPivot('order')
			->withTimestamps()
			->orderByPivot('order');
	}
}
