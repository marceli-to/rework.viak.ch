<?php

declare(strict_types=1);

namespace App\Support\Payments;

/**
 * What a payment provider reports about a paid checkout ([[PaymentGateway]]):
 * which invoice it was for, how much was taken and under which reference.
 * Read from the provider, never from the browser.
 */
final class CardPayment
{
	public function __construct(
		public readonly string $invoice,
		public readonly int $amount,
		public readonly string $currency,
		public readonly string $reference,
	) {}
}
