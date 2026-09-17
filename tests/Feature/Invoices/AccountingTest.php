<?php

declare(strict_types=1);

use App\Actions\Events\SetEventState;
use App\Actions\Invoices\SyncInvoiceStatus;
use App\Enums\EventState;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Invoice;
use App\Support\Accounting\AccountingSystem;
use App\Support\Accounting\FakeAccountingSystem;
use App\Support\Accounting\RunMyAccounts;

/**
 * The boundary to VIAK's books ([[AccountingSystem]]).
 *
 * The standing constraint, and the reason any of this is an interface:
 * **nothing may be posted to the client's live accounting from a prototype.**
 * A rework that quietly writes invoices into the books VIAK files their VAT
 * from is the one mistake here that cannot be undone.
 */
it('binds the fake accounting system outside production', function () {
	expect(app(AccountingSystem::class))->toBeInstanceOf(FakeAccountingSystem::class);
});

it('keeps the real client unreachable even with credentials configured', function () {
	config()->set('services.run_my_accounts', [
		'base_url' => 'https://example.test/api',
		'key' => 'a-key-that-should-never-be-used-here',
		'create_path' => '/invoice',
		'status_path' => '/invoice/%INVOICE_NO%',
		'prefix' => 'VIAK_',
	]);

	app()->forgetInstance(AccountingSystem::class);

	expect(app(AccountingSystem::class))->toBeInstanceOf(FakeAccountingSystem::class);
});

/**
 * The opposite failure is the one worth having: an invoice missing from the
 * books can be reconciled, while books charged for an invoice nobody issued
 * cannot be repaired. So the real client refuses to exist without credentials
 * rather than silently doing nothing.
 */
it('refuses to build the real client without credentials', function () {
	new RunMyAccounts(baseUrl: '', apiKey: '', createPath: '', statusPath: '');
})->throws(RuntimeException::class, 'not configured');

it('posts every invoice it issues to the books', function () {
	$event = Event::factory()->create();
	Booking::factory()->for($event)->count(2)->create();

	app(SetEventState::class)->execute($event, EventState::Confirmed);

	$accounting = app(AccountingSystem::class);

	expect($accounting->recorded)->toHaveCount(2)
		->and($accounting->recordedNumbers())->toEqual(Invoice::pluck('number')->all());
});

/**
 * Payment is not something this application observes: the money arrives at the
 * bank, the books match it against the QR reference, and the site asks. Nobody
 * marks an invoice paid by hand, which is why there is no action to do so.
 */
it('takes payment from the books, not from a human', function () {
	$invoice = Invoice::factory()->create();

	app(AccountingSystem::class)->reports($invoice->number, InvoiceStatus::Paid);

	app(SyncInvoiceStatus::class)->execute($invoice);

	expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
		->and($invoice->fresh()->paid_at)->not->toBeNull();
});

it('leaves an invoice alone when the books have nothing to say', function () {
	$invoice = Invoice::factory()->create();

	expect(app(SyncInvoiceStatus::class)->execute($invoice))->toBeNull()
		->and($invoice->fresh()->status)->toBe(InvoiceStatus::Open);
});

it('counts only real changes when it sweeps the pending invoices', function () {
	$paid = Invoice::factory()->create();
	Invoice::factory()->count(2)->create();

	app(AccountingSystem::class)->reports($paid->number, InvoiceStatus::Paid);

	$this->artisan('invoices:sync')
		->expectsOutputToContain('Asked about 3 pending invoice(s), 1 changed.')
		->assertSuccessful();

	expect($paid->fresh()->status)->toBe(InvoiceStatus::Paid);
});

/**
 * Re-syncing must not move the date an invoice was paid on — and, in legacy,
 * this very sync was what destroyed `due_at`: every write reset it to now.
 */
it('does not move the payment date, or the deadline, when it syncs again', function () {
	$invoice = Invoice::factory()->paid()->create([
		'paid_at' => '2026-03-01 10:00:00',
		'due_at' => '2026-02-20',
	]);

	app(AccountingSystem::class)->reports($invoice->number, InvoiceStatus::Paid);
	app(SyncInvoiceStatus::class)->execute($invoice);

	expect($invoice->fresh()->paid_at->toDateString())->toBe('2026-03-01')
		->and($invoice->fresh()->due_at->toDateString())->toBe('2026-02-20');
});
