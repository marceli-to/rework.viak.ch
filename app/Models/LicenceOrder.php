<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A licence order ([[05-licences]]): the licence's counterpart to a booking.
 * It freezes what was sold, line by line, and is what the dispatch worklist
 * lists until every line has gone out.
 *
 * **Paid** has two sources: a free order is paid when placed (#35), a priced
 * one when its invoice is. A card payment sets `paid_at` too
 * ([[RecordCardPayment]]).
 */
class LicenceOrder extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = ['number', 'user_id', 'invoice_id', 'invoice_address', 'delivery_email', 'entered_by', 'paid_at', 'total', 'comment', 'cancelled_at', 'cancelled_by'];

	protected function casts(): array
	{
		return [
			'invoice_address' => 'array',
			'paid_at' => 'datetime',
			'total' => 'decimal:2',
			'cancelled_at' => 'datetime',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function invoice(): BelongsTo
	{
		return $this->belongsTo(Invoice::class);
	}

	public function enteredBy(): BelongsTo
	{
		return $this->belongsTo(User::class, 'entered_by');
	}

	public function canceller(): BelongsTo
	{
		return $this->belongsTo(User::class, 'cancelled_by');
	}

	public function items(): HasMany
	{
		return $this->hasMany(LicenceOrderItem::class)->orderBy('position');
	}

	/** Cancelled before anything was sent ([[CancelLicenceOrder]]). */
	public function isCancelled(): bool
	{
		return $this->cancelled_at !== null;
	}

	/** Brought over from 3d-software.ch ([[PortShop]]): the shop invoiced it, not us. */
	public function isFromShop(): bool
	{
		return $this->shop_order_id !== null;
	}

	/**
	 * `cancelled`, `free`, `paid` or `open`, for the badges in the dashboard
	 * and the profile. Free is an order with no invoice (#35), **unless it came
	 * from the shop**: those have no invoice here at all, so only a total of 0
	 * makes them free, and `paid_at` says the rest.
	 */
	public function payment(): string
	{
		return match (true) {
			$this->isCancelled() => 'cancelled',
			$this->isFromShop() => match (true) {
				bccomp((string) $this->total, '0', 2) <= 0 => 'free',
				$this->paid_at !== null => 'paid',
				default => 'open',
			},
			$this->invoice_id === null => 'free',
			$this->isPaid() => 'paid',
			$this->invoice?->isCancelled() ?? false => 'cancelled',
			default => 'open',
		};
	}

	/** What the customer pays, VAT included: the invoice's, or the shop's for an imported order. */
	public function grandTotal(): ?string
	{
		return $this->invoice ? (string) $this->invoice->grand_total : ($this->total !== null ? (string) $this->total : null);
	}

	public function isPaid(): bool
	{
		return $this->paid_at !== null || ($this->invoice?->isPaid() ?? false);
	}

	/** Where the licences are sent: the address given, else the account's own. */
	public function deliveryEmail(): string
	{
		return $this->delivery_email ?: (string) $this->user?->email;
	}

	/** The sum of the lines, net: what the invoice was raised over, before VAT. */
	public function net(): string
	{
		return $this->items->reduce(fn (string $sum, LicenceOrderItem $item) => bcadd($sum, $item->net(), 2), '0.00');
	}

	/** On the worklist: a line still to send, and not cancelled. */
	public function scopeOutstanding(Builder $query): void
	{
		$query->whereNull('cancelled_at')->whereHas('items', fn (Builder $items) => $items->whereNull('dispatched_at'));
	}

	public function scopeDispatched(Builder $query): void
	{
		$query->whereNull('cancelled_at')->whereDoesntHave('items', fn (Builder $items) => $items->whereNull('dispatched_at'));
	}

	public function scopeCancelled(Builder $query): void
	{
		$query->whereNotNull('cancelled_at');
	}
}
