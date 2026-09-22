<?php

declare(strict_types=1);

/*
 * Generated PDFs — the invoice, the participation confirmation, the participant
 * list ([[03-invoices]], [[08-accounts]]).
 *
 * This is the half of legacy's `config/invoice.php` that belongs to the
 * *document* rather than to the domain: who is being paid, into which account,
 * and what the page is made of. `config/invoice.php` kept the other half — the
 * VAT rate, the payment period, the penalty windows — and the split is the
 * reason neither file is twelve keys of unrelated things any more.
 */

return [

	/*
	 * The creditor, as it appears on the QR bill.
	 *
	 * **Structured, not combined**, and that is not a style choice: the Swiss
	 * QR-bill standard withdrew the combined address form, and
	 * `sprain/swiss-qr-bill` v5 removed `CombinedAddress` with it. Legacy passes
	 * `'Limmatstrasse 291'` and `'CH-8005 Zürich'` as two free-text lines; the
	 * street number, the postal code and the town are separate fields now, and
	 * the country is the ISO code on its own.
	 */
	'creditor' => [
		'name' => 'Visualisierungs-Akademie Schweiz GmbH',
		'street' => 'Limmatstrasse',
		'building_number' => '291',
		'postal_code' => '8005',
		'city' => 'Zürich',
		'country' => 'CH',
	],

	/*
	 * The QR-IBAN money is paid into. It is a *QR*-IBAN — the `3000 0` in the
	 * fifth to ninth position is a QR-IID — which is what obliges every bill to
	 * carry a 27-digit QR reference rather than a free-text one.
	 *
	 * Legacy also carries a `classic_iban` of `CHXX 0000 …`, a placeholder that
	 * is printed nowhere. Not carried across.
	 */
	'qr_iban' => env('DOCUMENTS_QR_IBAN', 'CH31 3000 0001 8767 6317 9'),

	'currency' => 'CHF',

	/*
	 * Where the bill is dated from. Legacy writes `Zürich, {date}` into three
	 * templates and the town is hardcoded in each.
	 */
	'place' => 'Zürich',

	/*
	 * The page furniture, as files on disk.
	 *
	 * **Local paths, deliberately.** Legacy's templates load the fonts with
	 * `url('{{ url("/") }}/assets/fonts/EffraRegular.ttf')` and the letterhead
	 * with `asset(…)`, so rendering a PDF makes three HTTP requests to the
	 * application's own public URL. On a queue worker with no outbound network,
	 * or with `APP_URL` pointing somewhere else, dompdf silently falls back to
	 * Helvetica and drops the logo — the invoice still renders and simply looks
	 * like a different company's. Read off the filesystem, a missing file is an
	 * exception at the moment it matters.
	 */
	'assets' => [
		'path' => resource_path('documents'),
		'letterhead' => resource_path('documents/letterhead.svg'),
		'fonts' => [
			'regular' => resource_path('documents/fonts/EffraRegular.ttf'),
			'bold' => resource_path('documents/fonts/EffraBold.ttf'),
		],
	],

	/*
	 * dompdf's own font cache. It compiles a TTF into its internal format once
	 * and keeps it here; the directory has to be writable and is disposable.
	 */
	'font_cache' => storage_path('app/dompdf'),

];
