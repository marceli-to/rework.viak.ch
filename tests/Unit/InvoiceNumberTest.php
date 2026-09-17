<?php

declare(strict_types=1);

use App\Support\InvoiceNumber;

/**
 * The transaction guard, tested here rather than in a Feature test because
 * `RefreshDatabase` wraps every one of those in a transaction of its own —
 * which would make the guard pass for the wrong reason ([[InvoiceNumber]]).
 *
 * The lock that keeps two confirmations in the same second from taking the
 * same number only holds inside a transaction, and `lockForUpdate` outside one
 * fails silently. Refusing is the only honest answer.
 */
it('refuses to mint an invoice number outside a transaction', function () {
	app(InvoiceNumber::class)->nextInTransaction();
})->throws(RuntimeException::class, 'inside a transaction');

it('pads a number to six digits, the way every invoice since 2023 is numbered', function () {
	expect(app(InvoiceNumber::class)->pad(570))->toBe('000570')
		->and(app(InvoiceNumber::class)->pad(1))->toBe('000001');
});
