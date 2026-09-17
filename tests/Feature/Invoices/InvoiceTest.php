<?php

declare(strict_types=1);

use App\Enums\CancellationReason;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\InvoiceNumber;

/**
 * The document, rather than the flow that produces it. What an invoice has to
 * keep saying once it has been sent ([[03-invoices]]).
 */

/**
 * The pin for the bug that cost every historical payment deadline.
 *
 * Legacy declared `due_at` a bare `timestamp`; it was the first TIMESTAMP
 * column in the table, so MySQL attached `ON UPDATE CURRENT_TIMESTAMP` and
 * every write to an invoice reset its deadline to now. All 541 paid invoices
 * lost their original, and open ones were bumped daily so they could never
 * fall due. Nothing in the Laravel code revealed it, which is exactly why this
 * test asserts on a column nobody touched.
 */
it('leaves due_at alone when an unrelated column is written', function () {
	$invoice = Invoice::factory()->create(['due_at' => '2026-03-01']);

	$invoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now()]);

	expect($invoice->fresh()->due_at->toDateString())->toBe('2026-03-01');
});

it('accepts an invoice with no deadline at all', function () {
	$invoice = Invoice::factory()->create(['due_at' => null]);

	expect($invoice->fresh()->due_at)->toBeNull();
});

/**
 * The payoff of the line-items decision: today VIAK sends two invoices, two
 * numbers and two QR bills for one booking with a laptop.
 */
it('adds its lines up, and taxes only the taxable one', function () {
	$invoice = Invoice::factory()->create();

	InvoiceItem::factory()->for($invoice)->create(['net' => '600.00', 'vat_rate' => '0.00'])->computeAmounts()->save();
	InvoiceItem::factory()->for($invoice)->rental()->create(['position' => 2]);

	$invoice->storeTotalsFromItems();
	$invoice->refresh();

	expect($invoice->net)->toBe('680.00')
		->and($invoice->vat)->toBe('6.48')
		->and($invoice->grand_total)->toBe('686.48');
});

it('takes the discount off the line before taxing it', function () {
	$item = InvoiceItem::factory()->rental()->create(['net' => '80.00', 'discount' => '30.00']);

	$item->computeAmounts();

	// 8.1 % of 50.00, not of 80.00.
	expect($item->vat)->toBe('4.05')
		->and($item->total)->toBe('54.05');
});

/**
 * A course renamed in 2027 must not retitle an invoice issued in 2024. The
 * description is frozen at issue, which is why it is a column and not a join.
 */
it('keeps saying what it said when the course is renamed', function () {
	$course = Course::factory()->create(['title' => ['de' => 'Blender Modeling']]);
	$item = InvoiceItem::factory()->create(['description' => 'Blender Modeling, 12.–13.03.2026']);

	$course->update(['title' => ['de' => 'Blender Grundkurs']]);

	expect($item->fresh()->description)->toBe('Blender Modeling, 12.–13.03.2026');
});

/**
 * A number that has been on a document a customer holds can never come back,
 * so cancelled and soft-deleted invoices keep theirs. 8 legacy rows are in
 * exactly this state.
 */
it('never reissues the number of a deleted invoice', function () {
	Invoice::factory()->create(['number' => '000569'])->delete();

	expect(app(InvoiceNumber::class)->next())->toBe('000570');
});

it('mints the first number when there are no invoices', function () {
	expect(app(InvoiceNumber::class)->next())->toBe('000001');
});

/**
 * The late-cancellation flow, 6 rows in the dump: the original invoice is
 * cancelled and a new one for the penalty takes its place. Legacy recorded
 * that as the English sentence "Replaced by Invoice No. 000182".
 */
it('links a cancelled invoice to the one that replaced it', function () {
	$replacement = Invoice::factory()->create(['number' => '000182']);
	$original = Invoice::factory()->cancelled()->create([
		'number' => '000176',
		'cancellation_reason' => CancellationReason::Replaced,
		'replaced_by_invoice_id' => $replacement->id,
	]);

	expect($original->replacedBy->number)->toBe('000182')
		->and($replacement->replaces->number)->toBe('000176')
		->and($original->cancellation_reason)->toBe(CancellationReason::Replaced);
});

it('reads both legacy cancellation sentences and nothing else', function () {
	expect(CancellationReason::fromLegacyText('Replaced by Invoice No. 000182'))->toBe(CancellationReason::Replaced)
		->and(CancellationReason::fromLegacyText('Invoice deleted because of cancelled booking'))->toBe(CancellationReason::BookingCancelled)
		->and(CancellationReason::fromLegacyText('something new'))->toBeNull()
		->and(CancellationReason::fromLegacyText(null))->toBeNull();
});

it('counts open and overdue invoices as still owed', function () {
	Invoice::factory()->create();
	Invoice::factory()->overdue()->create();
	Invoice::factory()->paid()->create();
	Invoice::factory()->cancelled()->create();

	expect(Invoice::pending()->count())->toBe(2)
		->and(Invoice::inStatus(InvoiceStatus::Paid)->count())->toBe(1);
});

/**
 * A question legacy could not answer: `due_at` rewrote itself to today on
 * every write, so nothing was ever past its deadline. Invoice 000552 was
 * dated 2026-08-13, marked OVERDUE, and claimed a deadline of today.
 */
it('finds invoices that are actually past their deadline', function () {
	Invoice::factory()->create(['due_at' => today()->subDay()]);
	Invoice::factory()->create(['due_at' => today()->addDay()]);
	Invoice::factory()->paid()->create(['due_at' => today()->subYear()]);

	expect(Invoice::overdueOn(today())->count())->toBe(1);
});

it('keeps the frozen billing address as the lines that were printed', function () {
	$invoice = Invoice::factory()->create([
		'invoice_address' => ['lines' => ['Antonia Haller', 'Kaiserstr. 76', '7752 Orsières']],
	]);

	expect($invoice->fresh()->invoice_address['lines'])->toHaveCount(3);
});

it('knows a line is for a course, a rental or a licence', function () {
	$item = InvoiceItem::factory()->create(['type' => InvoiceItemType::Licence]);

	expect($item->fresh()->type)->toBe(InvoiceItemType::Licence);
});
