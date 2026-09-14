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

	protected $fillable = ['code', 'type', 'amount', 'valid_from', 'valid_to', 'remarks'];

	protected function casts(): array
	{
		return [
			'type' => DiscountType::class,
			'amount' => 'decimal:2',
			'valid_from' => 'date',
			'valid_to' => 'date',
		];
	}

	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class);
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
