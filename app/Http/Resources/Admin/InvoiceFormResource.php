<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An invoice as *Rechnung bearbeiten* holds it — the address fields
 * [[SaveInvoiceAddressRequest]] takes back, and what the form only shows
 * ([[07-dashboard]], step 7).
 *
 * **Which address the fields start from.** The column holds three things
 * ([[Invoice::billingLines]]): a structured snapshot, which fills the fields
 * as it is; nothing (431 ported invoices), where the PDF prints the student's
 * own address, so the fields start from that; or the printed lines of 131
 * ported invoices, which cannot be split back into fields. Those start from
 * the student's address too, and `printed` says what the invoice says today,
 * so the admin sees the difference before saving over it.
 *
 * @mixin Invoice
 */
class InvoiceFormResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$address = $this->invoice_address;
		$structured = filled($address) && ! isset($address['lines']);
		$user = $this->user;

		$from = $structured ? $address : [
			'first_name' => $user?->first_name,
			'last_name' => $user?->last_name,
			'company' => $user?->company,
			'street' => $user?->street,
			'street_no' => $user?->street_no,
			'zip' => $user?->zip,
			'city' => $user?->city,
			'country_code' => $user?->country_code,
		];

		return [
			'uuid' => $this->uuid,
			'first_name' => $from['first_name'] ?? '',
			'last_name' => $from['last_name'] ?? '',
			'company' => $from['company'] ?? '',
			'street' => $from['street'] ?? '',
			'street_no' => $from['street_no'] ?? '',
			'zip' => $from['zip'] ?? '',
			'city' => $from['city'] ?? '',
			'country' => $from['country_code'] ?? 'ch',

			// Read, never sent ([[useResourceForm]]).
			'number' => $this->number,
			'date' => $this->date?->toDateString(),
			'grand_total' => (string) $this->grand_total,
			'student' => $user?->name,
			'editable' => $this->isPending(),
			'printed' => isset($address['lines']) ? $this->billingLines() : null,
			'document' => $this->document ? route('documents.show', $this->document) : null,
		];
	}
}
