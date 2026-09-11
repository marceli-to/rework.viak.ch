<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One calendar day of an Event. A two-day course has two of these; the
 * Event's own `date` is the first of them and is what everything sorts on.
 */
class EventDate extends Model
{
	protected $fillable = ['date', 'time_start', 'time_end'];

	protected function casts(): array
	{
		return ['date' => 'date'];
	}

	public function event(): BelongsTo
	{
		return $this->belongsTo(Event::class);
	}
}
