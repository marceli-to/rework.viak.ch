<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCode extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = ['code', 'type', 'amount', 'usage_limit', 'valid_from', 'valid_to', 'remarks'];

	protected function casts(): array
	{
		return [
			'type' => DiscountType::class,
			'amount' => 'decimal:2',
			'usage_limit' => 'integer',
			'valid_from' => 'date',
			'valid_to' => 'date',
		];
	}

	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class);
	}

	public function checkouts(): HasMany
	{
		return $this->hasMany(Checkout::class);
	}

	/**
	 * How many times this code has been spent.
	 *
	 * A checkout is one use however many courses it covered — that is what
	 * "the code discounts the order" means. Ported bookings have no checkout,
	 * so they are counted directly; cancelled ones still count, because the
	 * code was spent when it was accepted.
	 */
	public function timesUsed(): int
	{
		return $this->checkouts()->count()
			+ $this->bookings()->whereNull('checkout_id')->count();
	}

	/**
	 * Is the code valid *and* still available, on a given day?
	 *
	 * Two questions legacy could not ask separately. `isSingle()` returned true
	 * exactly when a code had no validity window, so the usage limit and the
	 * date range were the same two columns — and `isValid()` only checked the
	 * window when **both** dates were set, meaning a code with one date would
	 * have been valid forever. No row has one date, which is luck rather than
	 * design, and the rework does not rely on it.
	 */
	public function isRedeemableOn(\DateTimeInterface $date): bool
	{
		if ($this->trashed()) {
			return false;
		}

		if ($this->valid_from !== null && $this->valid_from->greaterThan($date)) {
			return false;
		}

		if ($this->valid_to !== null && $this->valid_to->lessThan($date)) {
			return false;
		}

		return $this->usage_limit === null || $this->timesUsed() < $this->usage_limit;
	}

	/**
	 * Open-ended at either end: a null `valid_from` means "always has been",
	 * a null `valid_to` means "still is". 48 of 107 codes have neither.
	 */
	public function scopeValidOn(Builder $query, \DateTimeInterface $date): void
	{
		$query
			->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date))
			->where(fn (Builder $q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date));
	}

	/** Discount in francs off a given course fee. */
	public function amountOff(string $fee): string
	{
		return $this->type->applyTo((string) $this->amount, $fee);
	}
}
