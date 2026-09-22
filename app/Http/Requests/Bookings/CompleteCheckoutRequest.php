<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookings;

/**
 * Confirming a basket ([[06-bookings]]).
 *
 * Extends the pricing request because the selection is identical — the server
 * re-prices from scratch either way — and adds the one thing confirmation needs
 * that browsing does not: **the total the customer was shown**.
 *
 * That field is not the price. It is the price the client *claims to have
 * displayed*, and it is used only to refuse a checkout whose total has moved
 * since. Legacy had no such check, so a basket left open while a fee was edited
 * charged whatever the number happened to be at `Booking::create()`.
 */
class CompleteCheckoutRequest extends PriceBasketRequest
{
	/** @return array<string, mixed> */
	public function rules(): array
	{
		return [
			...parent::rules(),
			'total_shown' => ['required', 'decimal:0,2'],
			/*
			 * **The same fields a `UserAddress` has**, settled 2026-09-22.
			 *
			 * Three shapes disagreed about a frozen billing address: the 126
			 * ported bookings carry `{"lines": […]}` because legacy stored a
			 * rendered HTML fragment and there is no honest way back to fields
			 * ([[LegacyInvoiceAddress]]); this request asked for a flat
			 * `name`; and `UserAddress::toSnapshot()` gives the real columns.
			 *
			 * The last one wins, because it is what the customer actually
			 * picks on the checkout's address step and the only one that
			 * carries a country and a street number — both of which an invoice
			 * needs. The history keeps its lines; everything captured from here
			 * on has fields ([[09-public-site]]).
			 */
			'invoice_address' => ['nullable', 'array'],
			'invoice_address.first_name' => ['nullable', 'string', 'max:255'],
			'invoice_address.last_name' => ['nullable', 'string', 'max:255'],
			'invoice_address.company' => ['nullable', 'string', 'max:255'],
			'invoice_address.street' => ['required_with:invoice_address', 'string', 'max:255'],
			'invoice_address.street_no' => ['nullable', 'string', 'max:20'],
			'invoice_address.zip' => ['required_with:invoice_address', 'string', 'max:20'],
			'invoice_address.city' => ['required_with:invoice_address', 'string', 'max:255'],
			'invoice_address.country_code' => ['required_with:invoice_address', 'string', 'size:2'],
		];
	}

	public function totalShown(): string
	{
		return number_format((float) $this->input('total_shown'), 2, '.', '');
	}

	/** @return array<string, mixed>|null */
	public function invoiceAddress(): ?array
	{
		return $this->input('invoice_address');
	}
}
