<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

	protected $fillable = ['quote', 'name', 'context', 'featured', 'publish', 'order'];

	/** @var array<int, string> */
	public $translatable = ['quote', 'context'];

	protected function casts(): array
	{
		return [
			'featured' => 'boolean',
			'publish' => 'boolean',
			'order' => 'integer',
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
