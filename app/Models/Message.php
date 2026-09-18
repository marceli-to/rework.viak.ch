<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A note posted to a course and mailed to everyone booked on it
 * ([[08-accounts]]).
 */
class Message extends Model
{
	use HasFactory;
	use HasMedia;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = ['event_id', 'user_id', 'subject', 'body'];

	public function event(): BelongsTo
	{
		return $this->belongsTo(Event::class);
	}

	/** The expert or admin who wrote it. */
	public function author(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	/**
	 * Who it was sent to, frozen at the moment of sending.
	 *
	 * Not derived from the event's current bookings, which would quietly rewrite
	 * history every time somebody cancelled.
	 */
	public function recipients(): BelongsToMany
	{
		// `created_at` only, and no `withTimestamps()`: a recipient row records
		// that a message was sent to somebody. There is no such thing as
		// updating it.
		return $this->belongsToMany(User::class)->withPivot('created_at');
	}
}
