<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Shared behaviour for the five course taxonomies (category, level, language,
 * software, tag). They are structurally identical; only their meaning differs.
 */
trait IsTaxonomy
{
	public function courses(): MorphToMany
	{
		return $this->morphToMany(Course::class, 'taxonomy', 'course_taxonomy');
	}

	public function scopePublished(Builder $query): Builder
	{
		return $query->where('publish', true);
	}

	public function scopeOrdered(Builder $query): Builder
	{
		return $query->orderBy('order');
	}
}
