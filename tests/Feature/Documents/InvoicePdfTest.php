<?php

declare(strict_types=1);

use App\Actions\Documents\RenderInvoice;
use App\Enums\DocumentType;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\UserDocument;
use App\Support\Documents\QrBill;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Zxing\QrReader;

/**
 * The invoice PDF and its Swiss QR bill ([[03-invoices]], [[08-accounts]]).
 *
 * Rendering is slow — dompdf compiles two TrueType faces on the first run — so
 * what is tested here is what can go wrong *silently*: the reference number,
 * which a bank reads; the debtor, which decides who a payment comes from; the
 * VAT rows, which legacy hardcoded into the template; and the filing, which
 * legacy did twice over with two methods that disagreed.
 */
function invoiceFor(User $user, array $attributes = []): Invoice
{
	return Invoice::factory()->create([
		'user_id' => $user->id,
		'number' => '000300',
		'date' => Carbon::parse('2024-10-02'),
		'due_at' => Carbon::parse('2024-10-12'),
		'net' => '989.00',
		'grand_total' => '989.00',
		...$attributes,
	]);
}

function invoiceCustomer(array $attributes = []): User
{
	return User::factory()->create([
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'street_no' => '76',
		'zip' => '7752',
		'city' => 'Orsières',
		'country_code' => 'ch',
		...$attributes,
	]);
}

beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Storage::fake('documents');
});

/*
|--------------------------------------------------------------------------
| The reference — the one number a bank reads
|--------------------------------------------------------------------------
*/

it('generates the reference legacy generated, character for character', function () {
    $invoice = invoiceFor(invoiceCustomer());

    /*
     * **The whole point of deleting 150 lines.** Legacy assembles this by hand
     * — `esr_customer_id . ' 00000 ' . clientNumber . ' ' . paddedInvoiceNumber`
     * plus a modulo-10 check digit from a lookup table — and it reads like an
     * ESR reference from the old orange slips. It is in fact a valid 27-digit
     * QR reference, and the library produces the identical string.
     *
     * Pinned against a **real ported invoice**: `viak-rechnung-02-10-2024-000300.pdf`,
     * which is in a customer's hands, prints `00 00000 00000 00000 00000 03009`.
     */
    expect(app(QrBill::class)->reference($invoice))->toBe('000000000000000000000003009');
});

it('puts the same reference in the QR code as on the slip', function () {
	$invoice = invoiceFor(invoiceCustomer());
	$html = app(QrBill::class)->html($invoice);

	// Legacy prints one string and encodes another *call* — the same value, but
	// nothing holds them together. Here the printed form is the encoded form.
	expect($html)->toContain('00 00000 00000 00000 00000 03009');
});

/*
|--------------------------------------------------------------------------
| Who is paying
|--------------------------------------------------------------------------
*/

it('names the debtor in the QR code, which legacy never filled in', function () {
	$invoice = invoiceFor(invoiceCustomer(), [
		'invoice_address' => [
			'first_name' => 'Anna', 'last_name' => 'Muster', 'company' => 'Muster AG',
			'street' => 'Bahnhofstrasse', 'street_no' => '12',
			'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
		],
	]);

	expect(app(QrBill::class)->html($invoice))->toContain('Muster AG Anna Muster');
});

it('falls back to the customer when no invoice address was frozen', function () {
	$invoice = invoiceFor(invoiceCustomer());

	expect(app(QrBill::class)->html($invoice))->toContain('Antonia Haller');
});

it('leaves the debtor box empty for a legacy address it cannot split', function () {
	$invoice = invoiceFor(invoiceCustomer(), [
		// The shape the 131 historical invoices carry: a rendered HTML fragment
		// with no fields to recover ([[LegacyInvoiceAddress]]).
		'invoice_address' => ['lines' => ['viscom, viscampus', 'Karin Buschor', 'Weihermattstrasse 94', '5000 Aarau']],
	]);

	// Empty is valid and prints an outlined box to write in. A guess would be a
	// payment arriving from the wrong party.
	expect(app(QrBill::class)->html($invoice))->not->toContain('Karin Buschor');

	// …and the invoice itself still prints exactly what was billed.
	expect($invoice->billingLines())->toBe(['viscom, viscampus', 'Karin Buschor', 'Weihermattstrasse 94', '5000 Aarau']);
});

it('refuses to render a bill it knows a bank would reject', function () {
	config()->set('documents.qr_iban', 'CH00 0000 0000 0000 0000 0');

	expect(fn () => app(QrBill::class)->html(invoiceFor(invoiceCustomer())))
		->toThrow(RuntimeException::class, 'is invalid');
});

/*
|--------------------------------------------------------------------------
| The document
|--------------------------------------------------------------------------
*/

it('renders a PDF and files it on the private disk', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user);
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

	$document = app(RenderInvoice::class)->execute($invoice);

	expect($document->type)->toBe(DocumentType::Invoice)
		->and($document->filename)->toBe('viak-rechnung-02-10-2024-000300.pdf')
		// Legacy's own filename pattern, so the 568 ported files and everything
		// written from here on read alike.
		->and($invoice->fresh()->filename)->toBe('viak-rechnung-02-10-2024-000300.pdf');

	Storage::disk('documents')->assertExists($document->path());

	expect(Storage::disk('documents')->get($document->path()))->toStartWith('%PDF-');
});

it('reaches the customer through the policy-gated route and nobody else', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user);
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

	$document = app(RenderInvoice::class)->execute($invoice);

	// Legacy wrote these under `public/storage`, where the only thing between an
	// invoice and anyone who wanted it was a uuid ([[08-accounts]], finding 3).
	$this->actingAs($user)->get(route('documents.show', $document))->assertOk();

	$this->actingAs(User::factory()->create())
		->get(route('documents.show', $document))
		->assertForbidden();
});

it('replaces its own document rather than filing a second one', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user);
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

	$first = app(RenderInvoice::class)->execute($invoice);
	$again = app(RenderInvoice::class)->execute($invoice);

	expect($again->id)->toBe($first->id)
		->and(UserDocument::query()->count())->toBe(1);
});

it('does not leave the old file behind when the date moves', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user);
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

	$first = app(RenderInvoice::class)->execute($invoice);
	$stale = $first->path();

	/*
	 * Legacy's `update()` re-renders under a filename built from the invoice's
	 * date and **does not touch the row**, so moving a date leaves the old PDF
	 * on disk for ever and the row pointing at it. `Todo.md` already counts 294
	 * such orphans on the live site.
	 */
	$invoice->update(['date' => Carbon::parse('2024-11-05')]);
	$renamed = app(RenderInvoice::class)->execute($invoice);

	expect($renamed->filename)->toBe('viak-rechnung-05-11-2024-000300.pdf');
	Storage::disk('documents')->assertExists($renamed->path());
	Storage::disk('documents')->assertMissing($stale);
});

/*
|--------------------------------------------------------------------------
| What the page says
|--------------------------------------------------------------------------
*/

it('prints one VAT row per rate, where legacy hardcoded the label', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user, ['net' => '579.00', 'vat' => '6.48', 'grand_total' => '585.48']);

	// A course and the laptop beside it — one document in the rework, two
	// invoice numbers and two QR bills in legacy ([[InvoiceItem]]).
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'position' => 1]);
	InvoiceItem::factory()->rental()->create(['invoice_id' => $invoice->id, 'position' => 2]);

	$html = view('documents.invoice', [
		'invoice' => $invoice->fresh()->load('items.itemable', 'user'),
		'qrBill' => '',
	])->render();

	expect($html)->toContain('Mehrwertsteuer (0%)')
		->and($html)->toContain('Mehrwertsteuer (8.1%)')
		->and($html)->toContain('Laptopmiete');
});

it('names the booking the invoice covers', function () {
	$user = invoiceCustomer();
	$invoice = invoiceFor($user);
	$booking = Booking::factory()->create(['user_id' => $user->id, 'number' => '000326']);
	InvoiceItem::factory()->create([
		'invoice_id' => $invoice->id,
		'itemable_type' => Booking::class,
		'itemable_id' => $booking->id,
	]);

	$html = view('documents.invoice', [
		'invoice' => $invoice->fresh()->load('items.itemable', 'user'),
		'qrBill' => '',
	])->render();

	expect($html)->toContain('Buchung 000326');
});

/*
|--------------------------------------------------------------------------
| The one check a human cannot do by looking
|--------------------------------------------------------------------------
*/

it('produces a QR code that scans out of the finished PDF', function () {
	if (! class_exists(QrReader::class) || ! extension_loaded('imagick')) {
		$this->markTestSkipped('needs the QR decoder and Imagick to rasterise the page');
	}

	$user = invoiceCustomer();
	$invoice = invoiceFor($user, [
		'invoice_address' => [
			'first_name' => 'Anna', 'last_name' => 'Muster', 'company' => 'Muster AG',
			'street' => 'Bahnhofstrasse', 'street_no' => '12',
			'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
		],
	]);
	InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

	$pdf = app(RenderInvoice::class)->preview($invoice);

	/*
	 * **Rasterised at print resolution and read back**, because everything
	 * short of this passes while the slip is unusable: the payload can be
	 * perfect and the image still be scaled, cropped or dithered into
	 * something no bank app will read. dompdf renders the library's SVG, and
	 * this is the only check that the thing on the paper works.
	 */
	$page = new Imagick;
	$page->setResolution(200, 200);
	$page->readImageBlob($pdf);
	$page->setIteratorIndex(1);
	$page = $page->getImage();
	$page->cropImage(460, 460, 480, 1590);
	$page->setImageFormat('png');

	$file = tempnam(sys_get_temp_dir(), 'qr').'.png';
	file_put_contents($file, $page->getImageBlob());

	$payload = (new QrReader($file))->text();
	@unlink($file);

	expect($payload)->not->toBeNull();

	$fields = preg_split('/\r\n|\n/', (string) $payload);

	expect($fields[0])->toBe('SPC')
		->and($fields[1])->toBe('0200')
		// The account, with the spaces the config writes for people stripped.
		->and($fields[3])->toBe('CH3130000001876763179')
		// `S` — a structured creditor address. The combined form the standard
		// withdrew would be `K` here ([[QrBill]]).
		->and($fields[4])->toBe('S')
		->and($fields)->toContain('QRR')
		->and($fields)->toContain('000000000000000000000003009')
		// The debtor legacy never encodes at all.
		->and($fields)->toContain('Muster AG Anna Muster')
		->and(end($fields))->toBe('EPD');
});
