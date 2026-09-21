<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * An embedded video on a course page ([[01-schema]]).
 *
 * `code` is an `<iframe>` an editor pasted, and the page prints it unescaped —
 * so this table is editor-only, exactly as legacy's was. Nothing a visitor
 * sends reaches it.
 */
class CourseVideo extends Model
{
	use HasFactory;
	use HasTranslations;
	use HasUuid;

	protected $fillable = ['title', 'code', 'order', 'publish', 'course_id'];

	/** @var array<int, string> */
	public $translatable = ['title'];

	protected function casts(): array
	{
		return [
			'order' => 'integer',
			'publish' => 'boolean',
		];
	}

	public function course(): BelongsTo
	{
		return $this->belongsTo(Course::class);
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
