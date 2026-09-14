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

	/** @return array<string, string|null> The snapshot a booking freezes. */
	public function toSnapshot(): array
	{
		return $this->only([
			'first_name', 'last_name', 'company',
			'street', 'street_no', 'zip', 'city', 'country_code',
		]);
	}
}
