<?php

declare(strict_types=1);

use App\Enums\CancellationReason;
use App\Enums\InvoiceStatus;
use App\Mail\CompanyCourseInvoice;
use App\Models\CompanyCourse;
use App\Models\Country;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Accounting\AccountingSystem;
use App\Support\Accounting\RunMyAccounts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * *Firmenkurse* — `/api/admin/company-courses` ([[16-firmenkurse]]): a
 * course for one company, billed to the address typed on it with no account,
 * in one invoice issued from lines typed on it.
 */
beforeEach(function () {
	Storage::fake('documents');
	Mail::fake();
	Country::firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function companyCoursePayload(array $overrides = []): array
{
	return [
		'company' => 'Muster AG',
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'street' => 'Bahnhofstrasse',
		'street_no' => '1',
		'zip' => '8001',
		'city' => 'Zürich',
		'country' => 'ch',
		'course' => '',
		'title' => 'Rhino für Architekten, intern',
		'location' => 'bei der Firma, Zürich',
		'participants' => 8,
		'dates' => [['date' => '2026-11-12', 'time_start' => '09:00', 'time_end' => '17:00'], ['date' => '2026-11-13', 'time_start' => '', 'time_end' => '']],
		'email' => 'buchhaltung@muster.test',
		'lines' => [
			['description' => 'Firmenschulung Rhino, 2 Tage, pauschal', 'net' => '4800', 'taxed' => false],
			['description' => 'Spesen', 'net' => '200', 'taxed' => true],
		],
		'remarks' => '',
		...$overrides,
	];
}

function createCompanyCourse(array $overrides = []): CompanyCourse
{
	$uuid = test()->postJson('/api/admin/company-courses', companyCoursePayload($overrides))->assertCreated()->json('data.uuid');

	return CompanyCourse::query()->where('uuid', $uuid)->sole();
}

it('keeps Firmenkurse to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/company-courses')->assertForbidden();
});

it('wants the address the invoice goes to', function () {
	$this->actingAs($this->admin)->postJson('/api/admin/company-courses', companyCoursePayload(['company' => '', 'zip' => '']))
		->assertJsonValidationErrors(['company', 'zip']);
});

it('saves a Firmenkurs with a typed title, its days and its lines', function () {
	$this->actingAs($this->admin);
	$course = createCompanyCourse();

	expect($course->name())->toBe('Rhino für Architekten, intern')
		->and($course->dates)->toHaveCount(2)
		->and($course->lines->pluck('description')->all())->toBe(['Firmenschulung Rhino, 2 Tage, pauschal', 'Spesen'])
		->and($course->invoice_id)->toBeNull();
});

it('takes a catalogue course in place of a title, and wants one of the two', function () {
	$this->actingAs($this->admin);
	$catalogue = Course::factory()->create(['title' => ['de' => 'Rhino Grundlagen']]);

	expect(createCompanyCourse(['title' => '', 'course' => $catalogue->uuid])->name())->toBe('Rhino Grundlagen');

	$this->postJson('/api/admin/company-courses', companyCoursePayload(['title' => '']))
		->assertJsonValidationErrors(['title' => 'Bitte einen Kurs wählen oder einen Titel erfassen.']);
});

it('issues one invoice to the company, no account, the expenses taxed and the course not', function () {
	$this->actingAs($this->admin);
	$course = createCompanyCourse();

	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertOk()
		->assertJsonPath('data.locked', true)
		->assertJsonPath('data.invoice.status', InvoiceStatus::Open->value);

	$invoice = Invoice::query()->sole();

	expect($invoice->user_id)->toBeNull()
		->and($invoice->company_course_id)->toBe($course->id)
		->and($invoice->invoice_address)->toMatchArray(['company' => 'Muster AG', 'first_name' => 'Anna', 'zip' => '8001'])
		->and($invoice->items->pluck('vat_rate')->map(fn ($rate) => (string) $rate)->all())->toBe(['0.00', '8.10'])
		->and((string) $invoice->net)->toBe('5000.00')
		->and((string) $invoice->vat)->toBe('16.20')
		->and((string) $invoice->grand_total)->toBe('5016.20');

	Storage::disk('documents')->assertExists($invoice->companyPdfPath());
	Mail::assertQueued(CompanyCourseInvoice::class, fn ($mail) => $mail->hasTo('buchhaltung@muster.test'));
	expect(app(AccountingSystem::class)->recorded)->toHaveCount(1);
});

it('lists the invoice under Rechnungen by the company, with its PDF for admins only', function () {
	$this->actingAs($this->admin);
	$course = createCompanyCourse();
	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertOk();

	$row = $this->getJson('/api/admin/invoices?status=offen&suche=Muster')->assertOk()->json('data.0');

	expect($row['customer'])->toBe(['name' => 'Muster AG', 'city' => 'Zürich'])
		->and($row['document'])->toContain('/dokumente/firmenrechnung/');

	$this->get($row['document'])->assertOk();
	$this->actingAs(User::factory()->create())->get($row['document'])->assertForbidden();
});

it('files each Firmenkurs under its own customer number in Run My Accounts', function () {
	Http::fake();
	$this->actingAs($this->admin);
	$course = createCompanyCourse();
	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertOk();

	(new RunMyAccounts(baseUrl: 'https://rma.test', apiKey: 'key', createPath: '/invoice', statusPath: '/invoice/%INVOICE_NO%'))
		->record(Invoice::query()->sole());

	Http::assertSent(fn ($request) => $request['customer'] === ['customernumber' => 'VIAK_F'.$course->id, 'name' => 'Muster AG']);
});

it('refuses to invoice a Firmenkurs with nothing to bill', function () {
	$this->actingAs($this->admin);
	$course = createCompanyCourse(['lines' => []]);

	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertJsonValidationErrors('lines');
	expect(Invoice::query()->count())->toBe(0);
});

it('freezes an invoiced Firmenkurs until its invoice is withdrawn, then invoices it again', function () {
	$this->actingAs($this->admin);
	$course = createCompanyCourse();
	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertOk();

	$this->putJson("/api/admin/company-courses/{$course->uuid}", companyCoursePayload())->assertStatus(409);
	$this->deleteJson("/api/admin/company-courses/{$course->uuid}")->assertStatus(422);
	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertStatus(409);

	$this->patchJson("/api/admin/company-courses/{$course->uuid}/invoice/withdraw")->assertOk()->assertJsonPath('data.locked', false);

	$first = Invoice::query()->sole();
	expect($first->status)->toBe(InvoiceStatus::Cancelled)
		->and($first->cancellation_reason)->toBe(CancellationReason::Withdrawn);

	$this->putJson("/api/admin/company-courses/{$course->uuid}", companyCoursePayload([
		'lines' => [['description' => 'Firmenschulung Rhino, 3 Tage, pauschal', 'net' => '7200', 'taxed' => false]],
	]))->assertOk();
	$this->postJson("/api/admin/company-courses/{$course->uuid}/invoice")->assertOk();

	expect(Invoice::query()->count())->toBe(2)
		->and((string) $course->refresh()->invoice->grand_total)->toBe('7200.00');
});
