<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One completed checkout ([[06-bookings]]).
 *
 * The record of what was agreed at the till, so that an order-level discount has
 * something to be level with. Nothing is invoiced *from* it — invoices are
 * raised when an event is confirmed, a mean 27.7 days later — it is read *by*
 * whatever is being invoiced.
 */
class Checkout extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = [
		'user_id', 'discount_code_id', 'discount_amount', 'net',
		'invoice_address', 'completed_at',
	];

	protected function casts(): array
	{
		return [
			'discount_amount' => 'decimal:2',
			'net' => 'decimal:2',
			'invoice_address' => 'array',
			'completed_at' => 'datetime',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function discountCode(): BelongsTo
	{
		return $this->belongsTo(DiscountCode::class);
	}

	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class);
	}

	/**
	 * How much of this checkout's discount is still unspent.
	 *
	 * The draw-down, and the reason there is no `discount_consumed` column: what
	 * has been spent is *what the invoices say*, so asking them is both the
	 * truth and self-correcting. Cancel an invoice and its share becomes
	 * available again, which is exactly right — the customer was promised that
	 * money and a withdrawn document did not deliver it.
	 *
	 * Why draw-down at all, rather than splitting the discount proportionally at
	 * checkout: a split strands money. CHF 50 across a 949 and a 499 course
	 * gives 32.77 and 17.23, and if the second course never runs the customer
	 * receives 32.77 of the 50 they were promised. Drawing down means the full
	 * amount lands on whatever is invoiced **first**, so nothing is stranded, no
	 * rounding rule has to be got right, and no raised invoice is ever edited.
	 *
	 * Percentage codes never reach this path — a rate applied per line sums
	 * correctly by construction, which is why the legacy bug was invisible for
	 * three years.
	 */
	public function remainingDiscount(): string
	{
		$total = (string) $this->discount_amount;

		if (bccomp($total, '0.00', 2) <= 0) {
			return '0.00';
		}

		$spent = (string) (InvoiceItem::query()
			->whereHasMorph('itemable', [Booking::class], fn ($query) => $query->where('checkout_id', $this->id))
			->whereHas('invoice', fn ($query) => $query->where('status', '!=', InvoiceStatus::Cancelled))
			->sum('discount') ?: '0.00');

		$remaining = bcsub($total, $spent, 2);

		return bccomp($remaining, '0.00', 2) > 0 ? $remaining : '0.00';
	}
}
