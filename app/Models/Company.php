<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company VIAK trains, as an invoice address and nothing more
 * ([[16-firmenkurse]]): no account, no login. Kept so a second Firmenkurs
 * picks it again, because its id is the customer number Run My Accounts files
 * its invoices under ([[RunMyAccounts]]).
 *
 * The fields are [[UserAddress]]'s, so the snapshot an invoice freezes has
 * the shape every other one has.
 */
class Company extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'company', 'first_name', 'last_name',
		'street', 'street_no', 'zip', 'city', 'country_code',
	];

	public function country(): BelongsTo
	{
		return $this->belongsTo(Country::class, 'country_code', 'code');
	}

	public function courses(): HasMany
	{
		return $this->hasMany(CompanyCourse::class);
	}

	public function invoices(): HasMany
	{
		return $this->hasMany(Invoice::class);
	}

	/** Company, contact, city: what the picker lists ([[UserAddress::summary]]). */
	public function summary(): string
	{
		return implode(', ', array_filter([
			$this->company,
			trim("{$this->first_name} {$this->last_name}"),
			$this->city,
		], 'filled'));
	}

	/** @return array<string, string|null> The snapshot an invoice freezes ([[UserAddress::toSnapshot]]). */
	public function toSnapshot(): array
	{
		return $this->only([
			'first_name', 'last_name', 'company',
			'street', 'street_no', 'zip', 'city', 'country_code',
		]);
	}
}
