<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A generated PDF belonging to one customer ([[08-accounts]]).
 */
class UserDocument extends Model
{
	use HasFactory;
	use HasUuid;

	protected $fillable = [
		'user_id', 'type', 'filename', 'date',
		'documentable_type', 'documentable_id',
	];

	protected function casts(): array
	{
		return [
			'type' => DocumentType::class,
			'date' => 'date',
		];
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function documentable(): MorphTo
	{
		return $this->morphTo();
	}

	/**
	 * The invoice this document *is*, where it is one.
	 *
	 * Measured on the ported data: all 568 `INVOICE` rows point at an `Invoice`
	 * and all 437 `PARTICIPATION_CONFIRMATION` rows at a `Booking`, so the
	 * morph is exact in both directions and this never has to guess.
	 */
	public function invoice(): ?Invoice
	{
		return $this->documentable instanceof Invoice ? $this->documentable : null;
	}

	/**
	 * The seat this document is about, whichever end it hangs off.
	 *
	 * A participation confirmation points at the booking directly. An invoice
	 * points at the invoice, and the booking is behind its **first line** —
	 * `invoice_items.itemable`, because [[03-invoices]] put the link on the line
	 * rather than on a column, so one invoice can cover several bookings.
	 *
	 * *Meine Dokumente* wants one course name per row and legacy showed the
	 * first, which is right in all but the handful of multi-line invoices; a
	 * list of them in a 3-of-12 column would be worse than the name of the one
	 * the customer is looking for.
	 */
	public function relatedBooking(): ?Booking
	{
		if ($this->documentable instanceof Booking) {
			return $this->documentable;
		}

		$itemable = $this->invoice()?->items->first()?->itemable;

		return $itemable instanceof Booking ? $itemable : null;
	}

	/**
	 * Where the file lives on the private disk.
	 *
	 * Derived rather than stored, which is the point. Legacy kept a `uri`
	 * column holding a **public** path — and got it wrong on 271 rows, where the
	 * separator between `files` and the user uuid is simply missing, so
	 * `/storage/filesf962c8c4-…` instead of `/storage/files/f962c8c4-…`. Every
	 * one of those resolves once the slash is put back, and **95 students have
	 * had 404ing download links since 2023**. A path that is computed cannot
	 * acquire a typo.
	 */
	public function path(): string
	{
		return 'documents/'.$this->user->uuid.'/'.$this->filename;
	}
}
