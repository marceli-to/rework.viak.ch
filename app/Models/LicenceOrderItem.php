<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a licence order ([[05-licences]]): a variant, or the free line
 * (#36) with only a typed title and price. Title, article number and unit
 * price are frozen when the order is placed. Sent per line, since one order
 * can go to two resellers.
 */
class LicenceOrderItem extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = ['licence_order_id', 'licence_variant_id', 'title', 'sku', 'host', 'price', 'quantity', 'position', 'dispatched_at', 'dispatched_by'];

	protected function casts(): array
	{
		return [
			'price' => 'decimal:2',
			'quantity' => 'integer',
			'position' => 'integer',
			'dispatched_at' => 'datetime',
		];
	}

	public function order(): BelongsTo
	{
		return $this->belongsTo(LicenceOrder::class, 'licence_order_id');
	}

	/** The variant, deleted or not: an order outlives the catalogue. */
	public function variant(): BelongsTo
	{
		return $this->belongsTo(LicenceVariant::class, 'licence_variant_id')->withTrashed();
	}

	public function dispatcher(): BelongsTo
	{
		return $this->belongsTo(User::class, 'dispatched_by');
	}

	public function isDispatched(): bool
	{
		return $this->dispatched_at !== null;
	}

	/** Unit price times quantity. */
	public function net(): string
	{
		return bcmul((string) $this->price, (string) $this->quantity, 2);
	}

	/**
	 * What the invoice line says, frozen there in turn: the quantity in front
	 * where there is more than one, the host after, as VIAK orders it.
	 */
	public function description(): string
	{
		return ($this->quantity > 1 ? "{$this->quantity} × " : '')
			.$this->title
			.($this->host ? ", für {$this->host}" : '');
	}
}
