<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingCancellationReason;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
		'number', 'event_id', 'user_id', 'checkout_id', 'course_fee',
		'discount_code_id', 'discount_amount', 'has_rental', 'rental_fee',
		'invoice_address', 'booked_at', 'cancelled_at', 'cancellation_reason',
	];

	protected function casts(): array
	{
		return [
			'course_fee' => 'decimal:2',
			'discount_amount' => 'decimal:2',
			'has_rental' => 'boolean',
			'rental_fee' => 'decimal:2',
			'invoice_address' => 'array',
			'booked_at' => 'datetime',
			'cancelled_at' => 'datetime',
			'cancellation_reason' => BookingCancellationReason::class,
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

	/**
	 * The till this seat was sold at, where one exists.
	 *
	 * Null for all 710 ported bookings — legacy's basket lived in the session
	 * and left nothing behind — and null for a booking an admin creates by
	 * hand. Both are correct: neither went through a checkout, so neither has
	 * an order-level discount to draw on ([[06-bookings]]).
	 */
	public function checkout(): BelongsTo
	{
		return $this->belongsTo(Checkout::class);
	}

	public function isCancelled(): bool
	{
		return $this->cancelled_at !== null;
	}

	/**
	 * Is this seat still changeable without money being involved?
	 *
	 * The rental can be added or dropped for free right up until the invoice is
	 * raised, and not afterwards — which is the whole window legacy's
	 * `addRental`/`cancelRental` operated in without ever saying so. Once
	 * invoices are raised on confirmation, the reach into the invoice layer that
	 * legacy's version needed simply disappears.
	 */
	public function isEditable(): bool
	{
		return ! $this->isCancelled() && ! $this->isInvoiced();
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

	/**
	 * The invoices this booking appears on — through the line, not a column.
	 * Usually one; six historical bookings have two, because a late
	 * cancellation cancels the original invoice and raises a penalty one.
	 */
	public function invoiceItems(): MorphMany
	{
		return $this->morphMany(InvoiceItem::class, 'itemable');
	}

	/**
	 * Has this booking already been billed? Anything cancelled does not count
	 * — a cancelled invoice is one that was withdrawn, and the booking still
	 * owes for the seat.
	 */
	public function isInvoiced(): bool
	{
		return $this->invoiceItems()
			->whereHas('invoice', fn (Builder $query) => $query->where('status', '!=', InvoiceStatus::Cancelled))
			->exists();
	}
}
