<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One day of a Firmenkurs, as [[EventDate]] is one of a Veranstaltung. */
class CompanyCourseDate extends Model
{
	protected $fillable = ['date', 'time_start', 'time_end'];

	protected function casts(): array
	{
		return ['date' => 'date'];
	}

	public function companyCourse(): BelongsTo
	{
		return $this->belongsTo(CompanyCourse::class);
	}
}
