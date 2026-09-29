<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of *Rechnungen* ([[07-dashboard]], step 7): legacy's number, date,
 * amount and *Name, Ort*, and where the PDF is.
 *
 * @mixin Invoice
 */
class InvoiceRowResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'uuid' => $this->uuid,
			'number' => $this->number,
			'date' => $this->date?->toDateString(),
			'grand_total' => (string) $this->grand_total,
			'status' => $this->status->value,
			'editable' => $this->isPending(),
			'student' => $this->user ? ['name' => $this->user->name, 'city' => $this->user->city] : null,
			// Through the policy-guarded route, not the public disk legacy linked to.
			'document' => $this->document ? route('documents.show', $this->document) : null,
		];
	}
}
