<?php

declare(strict_types=1);

use App\Actions\Documents\RenderInvoice;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * *Rechnungen* — `/api/admin/invoices` ([[07-dashboard]], step 7).
 */
beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	Country::query()->firstOrCreate(['code' => 'de'], ['name' => ['de' => 'Deutschland'], 'order' => 2]);
	Storage::fake('documents');
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function billedStudent(array $attributes = []): User
{
	return User::factory()->student()->create([
		'first_name' => 'Antonia', 'last_name' => 'Haller', 'company' => null,
		'street' => 'Kaiserstr.', 'street_no' => '76', 'zip' => '7752', 'city' => 'Orsières', 'country_code' => 'ch',
		...$attributes,
	]);
}

function addressPayload(array $overrides = []): array
{
	return [
		'first_name' => '', 'last_name' => '', 'company' => 'Muster AG',
		'street' => 'Bahnhofstrasse', 'street_no' => '1', 'zip' => '8001', 'city' => 'Zürich', 'country' => 'ch',
		...$overrides,
	];
}

it('keeps invoices and the export to admins', function () {
	$student = User::factory()->student()->create();
	$invoice = Invoice::factory()->create();

	$this->actingAs($student)->getJson('/api/admin/invoices?status=offen')->assertForbidden();
	$this->actingAs($student)->getJson("/api/admin/invoices/{$invoice->uuid}")->assertForbidden();
	$this->actingAs($student)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload())->assertForbidden();
	$this->actingAs($student)->get('/api/admin/exports/courses')->assertForbidden();
});

it('lists each status apart, newest number first', function () {
	Invoice::factory()->create(['number' => '000010']);
	Invoice::factory()->create(['number' => '000011']);
	Invoice::factory()->overdue()->create(['number' => '000012']);
	Invoice::factory()->paid()->create(['number' => '000013']);
	Invoice::factory()->cancelled()->create(['number' => '000014']);

	$list = fn (string $status) => $this->actingAs($this->admin)->getJson("/api/admin/invoices?status={$status}")->assertOk()->json('data.*.number');

	expect($list('offen'))->toBe(['000011', '000010'])
		->and($list('faellig'))->toBe(['000012'])
		->and($list('bezahlt'))->toBe(['000013'])
		->and($list('storniert'))->toBe(['000014']);

	$this->actingAs($this->admin)->getJson('/api/admin/invoices?status=irgendwas')->assertNotFound();
});

it('searches every word against the number and the student', function () {
	Invoice::factory()->for(billedStudent(['first_name' => 'Priska', 'last_name' => 'Ott', 'city' => 'Fürstenau']))->create(['number' => '000552']);
	Invoice::factory()->for(billedStudent(['first_name' => 'Paul', 'last_name' => 'Ott', 'city' => 'Basel']))->create(['number' => '000553']);

	$search = fn (string $words) => $this->actingAs($this->admin)->getJson('/api/admin/invoices?status=offen&suche='.urlencode($words))->json('data.*.number');

	expect($search('ott'))->toBe(['000553', '000552'])
		->and($search('ott basel'))->toBe(['000553'])
		->and($search('552'))->toBe(['000552'])
		->and($search('priska 553'))->toBe([]);
});

it('links a row to its PDF through the guarded route, and marks only the owed ones editable', function () {
	$open = Invoice::factory()->for(billedStudent())->create();
	$paid = Invoice::factory()->for(billedStudent())->paid()->create();
	$document = app(RenderInvoice::class)->execute($open);

	$row = $this->actingAs($this->admin)->getJson('/api/admin/invoices?status=offen')->json('data.0');
	expect($row['document'])->toBe(route('documents.show', $document))
		->and($row['editable'])->toBeTrue()
		->and($row['student'])->toBe(['name' => 'Antonia Haller', 'city' => 'Orsières']);

	$this->actingAs($this->admin)->get($row['document'])->assertOk();

	$row = $this->actingAs($this->admin)->getJson('/api/admin/invoices?status=bezahlt')->json('data.0');
	expect($row['editable'])->toBeFalse()->and($row['document'])->toBeNull();
});

it('starts the form from the frozen address, or from the student where there is none', function () {
	$student = billedStudent();
	$frozen = Invoice::factory()->for($student)->create(['invoice_address' => [
		'first_name' => null, 'last_name' => null, 'company' => 'Haller GmbH',
		'street' => 'Hauptgasse', 'street_no' => '3', 'zip' => '3011', 'city' => 'Bern', 'country_code' => 'ch',
	]]);
	$none = Invoice::factory()->for($student)->create(['invoice_address' => null]);
	$ported = Invoice::factory()->for($student)->create(['invoice_address' => ['lines' => ['Priska Ott', 'Coppetstrasse 147', '9743 Fürstenau']]]);

	$form = fn (Invoice $invoice) => $this->actingAs($this->admin)->getJson("/api/admin/invoices/{$invoice->uuid}")->assertOk()->json('data');

	expect($form($frozen))->toMatchArray(['company' => 'Haller GmbH', 'first_name' => '', 'city' => 'Bern', 'printed' => null])
		->and($form($none))->toMatchArray(['first_name' => 'Antonia', 'last_name' => 'Haller', 'city' => 'Orsières', 'country' => 'ch', 'printed' => null])
		// The printed lines cannot be split into fields; they are shown instead.
		->and($form($ported))->toMatchArray(['first_name' => 'Antonia', 'printed' => ['Priska Ott', 'Coppetstrasse 147', '9743 Fürstenau']]);
});

it('changes the address of an owed invoice, and makes its PDF again', function () {
	$invoice = Invoice::factory()->for(billedStudent())->overdue()->create(['grand_total' => '600.00', 'invoice_address' => null]);
	$before = app(RenderInvoice::class)->execute($invoice);

	$this->actingAs($this->admin)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload())
		->assertOk()
		->assertJsonPath('data.company', 'Muster AG');

	$invoice->refresh();
	// `toEqual`: a JSON column hands the keys back in its own order.
	expect($invoice->invoice_address)->toEqual([
		'first_name' => null, 'last_name' => null, 'company' => 'Muster AG',
		'street' => 'Bahnhofstrasse', 'street_no' => '1', 'zip' => '8001', 'city' => 'Zürich', 'country_code' => 'ch',
	])
		->and($invoice->billingLines())->toBe(['Muster AG', 'Bahnhofstrasse 1', '8001 Zürich'])
		->and($invoice->document->is($before))->toBeTrue();

	// Rendered again from the new address: the one PDF, rewritten.
	Storage::disk('documents')->assertExists($invoice->document->path());
	expect(Storage::disk('documents')->lastModified($invoice->document->path()))->toBeGreaterThanOrEqual(now()->subMinute()->getTimestamp());
});

it('takes the address and nothing else', function () {
	$invoice = Invoice::factory()->for(billedStudent())->create(['grand_total' => '600.00', 'number' => '000700']);

	$this->actingAs($this->admin)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload([
		'grand_total' => '1.00', 'number' => '999999', 'status' => 'PAID',
	]))->assertOk();

	expect($invoice->refresh())
		->grand_total->toBe('600.00')
		->number->toBe('000700')
		->isPending()->toBeTrue();
});

it('asks for a pair of names or a firm, and a real country', function () {
	$invoice = Invoice::factory()->for(billedStudent())->create();

	$this->actingAs($this->admin)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload(['company' => '', 'country' => 'xx']))
		->assertJsonValidationErrors(['first_name', 'last_name', 'company', 'country']);

	$this->actingAs($this->admin)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload(['company' => '', 'first_name' => 'Eva', 'last_name' => 'Keller', 'country' => 'de']))
		->assertOk();
});

it('refuses to readdress a paid or cancelled invoice', function (string $state) {
	$invoice = Invoice::factory()->for(billedStudent())->{$state}()->create(['invoice_address' => null]);

	$this->actingAs($this->admin)->putJson("/api/admin/invoices/{$invoice->uuid}", addressPayload())->assertStatus(409);

	expect($invoice->refresh()->invoice_address)->toBeNull();
})->with(['paid', 'cancelled']);
