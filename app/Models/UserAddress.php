<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An address to send the bill to when it is not the student's own — usually
 * an employer paying for a staff member.
 */
class UserAddress extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'user_id', 'first_name', 'last_name', 'company',
		'street', 'street_no', 'zip', 'city', 'country_code',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function country(): BelongsTo
	{
		return $this->belongsTo(Country::class, 'country_code', 'code');
	}

	/**
	 * The address as it is read on screen, one line per line.
	 *
	 * Legacy's `getAddressAttribute()` built this as an HTML string with
	 * `<br>` in it and printed it through `v-html` — which is what the 126
	 * historical `invoice_address` snapshots are made of, and why
	 * [[LegacyInvoiceAddress]] cannot get fields back out of them. Lines, not
	 * markup: the view decides how to separate them.
	 *
	 * **The country only appears when it is not Switzerland**, as legacy has
	 * it. A Swiss address on a Swiss invoice does not need saying.
	 *
	 * @return array<int, string>
	 */
	public function lines(): array
	{
		$name = trim("{$this->first_name} {$this->last_name}");

		return array_values(array_filter([
			$this->company,
			$name,
			trim("{$this->street} {$this->street_no}"),
			trim("{$this->zip} {$this->city}"),
			$this->country_code === 'ch' ? null : $this->country?->name,
		], 'filled'));
	}

	/**
	 * The one-line form the invoice-address picker lists.
	 *
	 * Legacy's `address_str`, and **it has no street in it** — company, name,
	 * city — so two addresses at the same firm in the same town read
	 * identically in the dropdown. Carried across as found; it is their label,
	 * not a bug we introduced.
	 */
	public function summary(): string
	{
		$name = trim("{$this->first_name} {$this->last_name}");

		return implode(', ', array_filter([
			$this->company,
			$name,
			$this->city,
			$this->country_code === 'ch' ? null : $this->country?->name,
		], 'filled'));
	}

	/** @return array<string, string|null> The snapshot a booking freezes. */
	public function toSnapshot(): array
	{
		return $this->only([
			'first_name', 'last_name', 'company',
			'street', 'street_no', 'zip', 'city', 'country_code',
		]);
	}
}
