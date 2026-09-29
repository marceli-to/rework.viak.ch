<?php

declare(strict_types=1);

use App\Support\SiteUrl;

/**
 * *Zahlung per Kreditkarte* — a placeholder on legacy's URL until the Stripe
 * page is rebuilt (`Todo.md`, `Open-Questions.md` #28). The course
 * confirmation mail already links here.
 */
it('answers legacy card-payment URL with the placeholder', function () {
	$url = SiteUrl::invoicePayment('0b7c6c1e-1d2e-4f3a-9b8c-7d6e5f4a3b2c', 'de');

	expect($url)->toBe('/de/zahlung/rechnung/0b7c6c1e-1d2e-4f3a-9b8c-7d6e5f4a3b2c');

	$this->get($url)->assertOk()->assertSee('Zahlung per Kreditkarte')->assertSee('QR-Einzahlungsschein');
});

it('says nothing about any invoice, so it needs no sign-in', function () {
	$this->get('/de/zahlung/rechnung/0b7c6c1e-1d2e-4f3a-9b8c-7d6e5f4a3b2c')->assertOk()->assertDontSee('CHF');
});
