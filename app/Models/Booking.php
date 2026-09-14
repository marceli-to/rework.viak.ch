<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A seat on an Event, held by a User.
 *
 * A booking records what was *offered* — the fee at the time, any code the
 * student typed in. What was finally *charged* is the invoice's business, and
 * the two legitimately disagree: six historical bookings were invoiced at half
 * their `course_fee` under an arrangement recorded only on the invoice. Do not
 * add a total here; it would be a second answer to a question that already has
 * one.
 */
class Booking extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'number', 'event_id', 'user_id', 'course_fee',
		'discount_code_id', 'discount_amount', 'has_rental',
		'invoice_address', 'booked_at', 'cancelled_at',
	];

	protected function casts(): array
	{
		return [
			'course_fee' => 'decimal:2',
			'discount_amount' => 'decimal:2',
			'has_rental' => 'boolean',
			'invoice_address' => 'array',
			'booked_at' => 'datetime',
			'cancelled_at' => 'datetime',
		];
	}

	public function event(): BelongsTo
	{
		return $this->belongsTo(Event::class);
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function discountCode(): BelongsTo
	{
		return $this->belongsTo(DiscountCode::class);
	}

	public function isCancelled(): bool
	{
		return $this->cancelled_at !== null;
	}

	/**
	 * Cancelled seats do not count against an event's capacity, which is why
	 * this scope exists rather than a bare `bookings()` count. 183 of 710
	 * bookings are cancelled — getting this wrong would misreport every event
	 * as a quarter fuller than it is.
	 */
	public function scopeActive(Builder $query): void
	{
		$query->whereNull('cancelled_at');
	}

	/** What the student owes before VAT, which courses do not carry anyway. */
	public function netFee(): string
	{
		return bcsub((string) $this->course_fee, (string) $this->discount_amount, 2);
	}
}
