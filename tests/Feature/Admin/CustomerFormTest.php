<?php

declare(strict_types=1);

use App\Mail\EmailVerification;
use App\Models\Country;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * *Studenten* — `/api/admin/customers` ([[07-dashboard]], step 6): legacy's
 * student list and form on the field kit, and deactivating instead of
 * deleting (#16).
 */
beforeEach(function () {
	Country::firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function studentPayload(array $overrides = []): array
{
	return [
		'gender' => 'female',
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'company' => '',
		'email' => 'anna@example.test',
		'phone' => '044 123 45 67',
		'street' => 'Bahnhofstrasse',
		'street_no' => '1',
		'zip' => '8001',
		'city' => 'Zürich',
		'country' => 'ch',
		'subscribe_newsletter' => false,
		'roles' => [],
		'addresses' => [],
		...$overrides,
	];
}

function addressRow(array $overrides = []): array
{
	return [
		'uuid' => null, 'first_name' => '', 'last_name' => '', 'company' => 'Muster AG',
		'street' => 'Industriestrasse', 'street_no' => '5', 'zip' => '8400', 'city' => 'Winterthur', 'country' => 'ch',
		...$overrides,
	];
}

function studentAccount(array $attributes = []): User
{
	return User::factory()->create([
		'gender' => 'male', 'phone' => '031 000 00 00', 'street' => 'Marktgasse', 'zip' => '3011',
		'city' => 'Bern', 'country_code' => 'ch', ...$attributes,
	]);
}

it('keeps students to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/customers')->assertForbidden();
});

it('creates a customer with their addresses, and no role', function () {
	$uuid = $this->actingAs($this->admin)
		->postJson('/api/admin/customers', studentPayload(['addresses' => [addressRow()]]))
		->assertCreated()
		->json('data.uuid');

	$student = User::where('uuid', $uuid)->first();

	expect($student->roles())->toBeEmpty()
		->and($student->email_verified_at)->not->toBeNull()
		->and($student->addresses()->sole()->company)->toBe('Muster AG');
});

it('sends back exactly what it loads, addresses included', function () {
	$student = studentAccount();
	UserAddress::factory()->create(['user_id' => $student->id]);

	$form = $this->actingAs($this->admin)->getJson("/api/admin/customers/{$student->uuid}")->json('data');

	expect($form['addresses'])->toHaveCount(1)
		->and($this->putJson("/api/admin/customers/{$student->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('updates a kept address, adds a new one, and soft-deletes the one left out', function () {
	$student = studentAccount();
	$kept = UserAddress::factory()->create(['user_id' => $student->id]);
	$gone = UserAddress::factory()->create(['user_id' => $student->id]);

	$this->actingAs($this->admin)->putJson("/api/admin/customers/{$student->uuid}", studentPayload([
		'email' => $student->email,
		'addresses' => [addressRow(['uuid' => $kept->uuid, 'city' => 'Thun']), addressRow(['company' => 'Neu GmbH'])],
	]))->assertOk();

	expect($kept->refresh()->city)->toBe('Thun')
		->and(UserAddress::withTrashed()->find($gone->id)->trashed())->toBeTrue()
		->and($student->addresses()->pluck('company')->all())->toContain('Neu GmbH');
});

it("never touches another person's address through a borrowed uuid", function () {
	$student = studentAccount();
	$theirs = UserAddress::factory()->create(['user_id' => studentAccount()->id, 'city' => 'Basel']);

	$this->actingAs($this->admin)->putJson("/api/admin/customers/{$student->uuid}", studentPayload([
		'email' => $student->email,
		'addresses' => [addressRow(['uuid' => $theirs->uuid, 'city' => 'Chur'])],
	]))->assertOk();

	expect($theirs->refresh()->city)->toBe('Basel');
});

it('takes an address for a pair of names or a firm, not half a name', function () {
	$this->actingAs($this->admin);

	$this->postJson('/api/admin/customers', studentPayload(['email' => 'a@example.test', 'addresses' => [addressRow(['company' => '', 'first_name' => 'Hans', 'last_name' => 'Meier'])]]))->assertCreated();

	$this->postJson('/api/admin/customers', studentPayload(['email' => 'b@example.test', 'addresses' => [addressRow(['company' => '', 'first_name' => 'Hans'])]]))
		->assertJsonPath('errors', fn (array $errors) => ($errors['addresses.0.last_name'][0] ?? null) === 'Bitte Vor- und Nachname oder eine Firma erfassen.');
});

it('asks for the phone, as legacy does, in German', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/customers', studentPayload(['phone' => '']))
		->assertJsonPath('errors.phone.0', 'Telefon muss ausgefüllt sein.');
});

it('binds any account, since every account is a customer', function () {
	$expert = User::factory()->expert()->create();

	$this->actingAs($this->admin)->getJson("/api/admin/customers/{$expert->uuid}")->assertOk();
	$this->getJson('/api/admin/customers/'.Str::uuid())->assertNotFound();
});

it('will not let an admin take their own Admin role away here either', function () {
	$this->admin->forceFill(['gender' => 'male', 'phone' => '1', 'street' => 'x', 'zip' => '1', 'city' => 'x', 'country_code' => 'ch'])->save();

	$this->actingAs($this->admin)
		->putJson("/api/admin/customers/{$this->admin->uuid}", studentPayload(['email' => $this->admin->email, 'roles' => []]))
		->assertJsonPath('errors.roles.0', 'Du kannst dir die Admin-Rolle nicht selbst entziehen.');
});

it('pages every active account by name, and searches every word on the server', function () {
	studentAccount(['first_name' => 'Anna', 'last_name' => 'Zeller', 'city' => 'Zürich']);
	studentAccount(['first_name' => 'Anna', 'last_name' => 'Aebi', 'city' => 'Bern']);
	studentAccount(['first_name' => 'Beat', 'last_name' => 'Müller', 'city' => 'Zürich']);
	studentAccount(['last_name' => 'Weg', 'deactivated_at' => now()]);
	// Staff are accounts too, so customers (`12-customers.md`).
	User::factory()->expert()->create(['first_name' => 'Anna', 'last_name' => 'Brunner', 'city' => 'Basel']);
	$this->admin->update(['first_name' => 'Zora', 'last_name' => 'Zürcher']);

	$this->actingAs($this->admin);

	$all = $this->getJson('/api/admin/customers')->assertJsonPath('meta.total', 5)->json('data');
	expect(array_column($all, 'name'))->toBe(['Anna Aebi', 'Anna Brunner', 'Beat Müller', 'Anna Zeller', 'Zora Zürcher']);

	expect(array_column($this->getJson('/api/admin/customers?suche=anna+zürich')->json('data'), 'name'))->toBe(['Anna Zeller'])
		->and(array_column($this->getJson('/api/admin/customers?deaktiviert=1')->json('data'), 'name'))->toHaveCount(1);
});

it('takes a search literally, wildcards and all', function () {
	studentAccount(['last_name' => 'Muster']);

	$this->actingAs($this->admin)->getJson('/api/admin/customers?suche=%25')->assertJsonPath('meta.total', 0);
});

describe('deactivating (#16)', function () {
	it('deactivates and reactivates, and deletes nothing', function () {
		$student = studentAccount();

		$this->actingAs($this->admin)
			->patchJson("/api/admin/customers/{$student->uuid}/state", ['active' => false])
			->assertOk()
			->assertJsonPath('data.deactivated_at', fn ($at) => $at !== null);

		expect($student->refresh()->isDeactivated())->toBeTrue();

		$this->patchJson("/api/admin/customers/{$student->uuid}/state", ['active' => true])->assertJsonPath('data.deactivated_at', null);
		expect($student->refresh()->isDeactivated())->toBeFalse();

		$this->deleteJson("/api/admin/customers/{$student->uuid}")->assertStatus(405);
	});

	it('will not let an admin deactivate themselves', function () {
		$this->actingAs($this->admin)->patchJson("/api/admin/customers/{$this->admin->uuid}/state", ['active' => false])->assertStatus(422);

		expect($this->admin->refresh()->isDeactivated())->toBeFalse();
	});

	it('refuses to sign a deactivated account in, and says why', function () {
		$student = studentAccount(['deactivated_at' => now()]);

		$this->post('/login', ['email' => $student->email, 'password' => 'password'])
			->assertSessionHasErrors(['email' => 'Dieses Konto ist deaktiviert. Bitte melde dich bei uns, wenn das ein Irrtum ist.']);

		$this->assertGuest();
	});

	it('says nothing about deactivation to a wrong password', function () {
		$student = studentAccount(['deactivated_at' => now()]);

		$this->post('/login', ['email' => $student->email, 'password' => 'falsch'])
			->assertSessionHasErrors(['email' => __('auth.failed')]);
	});

	it('ends a session that was open when the account was deactivated', function () {
		$student = studentAccount();
		$this->actingAs($student)->get('/de/konto')->assertOk();

		$student->forceFill(['deactivated_at' => now()])->save();

		$this->get('/de/konto')->assertRedirect(route('login'));
		$this->assertGuest();
	});

	it('answers the API with a 401 once deactivated', function () {
		$student = studentAccount(['deactivated_at' => now()]);

		$this->actingAs($student)->getJson('/api/profile')->assertUnauthorized();
	});
});

it('has an address the admin changes confirmed by the student, and keeps an unchanged one verified', function () {
	$student = studentAccount(['email' => 'alt@example.test', 'email_verified_at' => now()]);
	Mail::fake();

	$this->actingAs($this->admin)->putJson("/api/admin/customers/{$student->uuid}", studentPayload(['email' => 'alt@example.test']))->assertOk();
	expect($student->refresh()->hasVerifiedEmail())->toBeTrue();
	Mail::assertNotQueued(EmailVerification::class);

	$this->putJson("/api/admin/customers/{$student->uuid}", studentPayload(['email' => 'neu@example.test']))
		->assertOk()
		->assertJsonPath('data.email_verified', false);
	Mail::assertQueued(EmailVerification::class, fn ($mail) => $mail->hasTo('neu@example.test'));
});
