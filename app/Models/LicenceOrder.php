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
 * one when its invoice is. The shop's card payment will set `paid_at` too
 * (`13-checkout.md`).
 */
class LicenceOrder extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = ['number', 'user_id', 'invoice_id', 'invoice_address', 'delivery_email', 'entered_by', 'paid_at'];

	protected function casts(): array
	{
		return [
			'invoice_address' => 'array',
			'paid_at' => 'datetime',
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

	public function items(): HasMany
	{
		return $this->hasMany(LicenceOrderItem::class)->orderBy('position');
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

	/** On the worklist: a line still to send. */
	public function scopeOutstanding(Builder $query): void
	{
		$query->whereHas('items', fn (Builder $items) => $items->whereNull('dispatched_at'));
	}

	public function scopeDispatched(Builder $query): void
	{
		$query->whereDoesntHave('items', fn (Builder $items) => $items->whereNull('dispatched_at'));
	}
}
