<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Mail\EmailVerification;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Event;
use App\Models\ExpertProfile;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * *Experten* — `/api/admin/experts` ([[07-dashboard]], step 6): legacy's
 * expert list and form, on the field kit.
 */
beforeEach(function () {
	Country::firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function expertPayload(array $overrides = []): array
{
	return [
		'gender' => 'female',
		'first_name' => 'Anna',
		'last_name' => 'Muster',
		'company' => '',
		'email' => 'anna@example.test',
		'phone' => '',
		'street' => 'Bahnhofstrasse',
		'street_no' => '1',
		'zip' => '8001',
		'city' => 'Zürich',
		'country' => 'ch',
		'subscribe_newsletter' => false,
		'visible' => true,
		'publish' => true,
		'roles' => ['expert'],
		'title' => 'Architektin',
		'description' => '<p>Unterrichtet Rhino.</p>',
		...$overrides,
	];
}

function expertAccount(array $profile = [], array $user = []): User
{
	// A whole address, as all twenty real experts have one.
	$expert = User::factory()->expert()->create([
		'gender' => 'male', 'street' => 'Marktgasse', 'zip' => '3011', 'city' => 'Bern', 'country_code' => 'ch', ...$user,
	]);
	ExpertProfile::factory()->create(['user_id' => $expert->id, ...$profile]);

	return $expert;
}

it('keeps experts to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/experts')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/experts', expertPayload())->assertForbidden();
});

it('creates an expert: the account, the profile, the roles', function () {
	ExpertProfile::factory()->create(['user_id' => User::factory()->expert()->create()->id, 'order' => 7]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/experts', expertPayload())->assertCreated()->json('data.uuid');
	$expert = User::where('uuid', $uuid)->first();

	expect($expert)
		->first_name->toBe('Anna')
		->country_code->toBe('ch')
		->email_verified_at->not->toBeNull()
		->and($expert->isExpert())->toBeTrue()
		->and($expert->isAdmin())->toBeFalse()
		->and($expert->expertProfile)
		->order->toBe(8)
		->title->toBe('Architektin')
		->publish->toBeTrue();
});

it('sends back exactly what it loads', function () {
	$expert = expertAccount();
	$form = $this->actingAs($this->admin)->getJson("/api/admin/experts/{$expert->uuid}")->json('data');

	expect($this->putJson("/api/admin/experts/{$expert->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('asks for the person and the address, in German', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/experts', expertPayload(['first_name' => '', 'zip' => '', 'gender' => '']))
		->assertJsonPath('errors.first_name.0', 'Vorname muss ausgefüllt sein.')
		->assertJsonPath('errors.zip.0', 'PLZ muss ausgefüllt sein.')
		->assertJsonValidationErrors('gender');
});

it('refuses an address another account already has, deleted ones too', function () {
	User::factory()->create(['email' => 'besetzt@example.test'])->delete();

	$this->actingAs($this->admin)
		->postJson('/api/admin/experts', expertPayload(['email' => 'besetzt@example.test']))
		->assertJsonPath('errors.email.0', 'Diese E-Mail-Adresse gehört bereits zu einem Konto.');
});

it('keeps an expert their own address on save', function () {
	$expert = expertAccount(user: ['email' => 'eigene@example.test']);

	$this->actingAs($this->admin)
		->putJson("/api/admin/experts/{$expert->uuid}", expertPayload(['email' => 'eigene@example.test']))
		->assertOk();
});

it('sets the roles exactly, Admin included', function () {
	$expert = expertAccount();
	$expert->syncRoles([Role::Expert, Role::Student]);

	$this->actingAs($this->admin)->putJson("/api/admin/experts/{$expert->uuid}", expertPayload(['roles' => ['admin', 'expert']]))->assertOk();

	expect(User::find($expert->id)->roles()->map->value->sort()->values()->all())->toBe(['admin', 'expert']);
});

it('asks for at least one role, and only real ones', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/experts', expertPayload(['roles' => []]))
		->assertJsonPath('errors.roles.0', 'Bitte mindestens eine Rolle wählen.');

	$this->postJson('/api/admin/experts', expertPayload(['roles' => ['superuser']]))->assertJsonValidationErrors('roles.0');
});

it('will not let an admin take their own Admin role away', function () {
	$this->admin->syncRoles([Role::Admin, Role::Expert]);

	$this->actingAs($this->admin)
		->putJson("/api/admin/experts/{$this->admin->uuid}", expertPayload(['email' => $this->admin->email, 'roles' => ['expert']]))
		->assertJsonPath('errors.roles.0', 'Du kannst dir die Admin-Rolle nicht selbst entziehen.');

	expect(User::find($this->admin->id)->isAdmin())->toBeTrue();
});

it('cleans the bio as the course texts are cleaned', function () {
	$expert = expertAccount();

	$this->actingAs($this->admin)->putJson("/api/admin/experts/{$expert->uuid}", expertPayload(['description' => '<p>Hallo<script>alert(1)</script></p>']));

	expect($expert->expertProfile->refresh()->description)->toBe('<p>Hallo</p>');
});

it('binds experts only', function () {
	$student = User::factory()->student()->create();

	$this->actingAs($this->admin)->getJson("/api/admin/experts/{$student->uuid}")->assertNotFound();
});

it('lists every expert in the Experten page order, unpublished ones too', function () {
	$b = expertAccount(['order' => 2, 'publish' => true]);
	$a = expertAccount(['order' => 1, 'publish' => true]);
	$off = expertAccount(['order' => 3, 'publish' => false]);
	User::factory()->student()->create();

	$rows = $this->actingAs($this->admin)->getJson('/api/admin/experts')->json('data');

	expect(array_column($rows, 'uuid'))->toBe([$a->uuid, $b->uuid, $off->uuid])
		->and($rows[2]['publish'])->toBeFalse()
		->and($rows[0])->toHaveKeys(['name', 'city', 'email']);
});

it('saves the order the list was dragged into', function () {
	$a = expertAccount(['order' => 1]);
	$b = expertAccount(['order' => 2]);

	$this->actingAs($this->admin)->postJson('/api/admin/experts/order', ['experts' => [$b->uuid, $a->uuid]])->assertNoContent();

	expect($b->expertProfile->refresh()->order)->toBe(1)
		->and($a->expertProfile->refresh()->order)->toBe(2);
});

it('deletes an expert nothing points at, portraits and all', function () {
	Storage::fake('public');
	$expert = expertAccount();
	$portrait = Media::factory()->for($expert, 'mediable')->create();

	$this->actingAs($this->admin)->getJson("/api/admin/experts/{$expert->uuid}")->assertJsonPath('data.has_history', false);
	$this->deleteJson("/api/admin/experts/{$expert->uuid}")->assertNoContent();

	expect(User::withTrashed()->find($expert->id))->toBeNull()
		->and(Media::find($portrait->id))->toBeNull()
		->and(ExpertProfile::where('user_id', $expert->id)->exists())->toBeFalse();
});

it('refuses to delete someone who has taught a date or booked one (#16)', function () {
	$teacher = expertAccount();
	Event::factory()->create()->experts()->attach($teacher);
	$booker = expertAccount();
	Booking::factory()->create(['user_id' => $booker->id]);

	$this->actingAs($this->admin)->getJson("/api/admin/experts/{$teacher->uuid}")->assertJsonPath('data.has_history', true);

	$this->deleteJson("/api/admin/experts/{$teacher->uuid}")->assertStatus(422);
	$this->deleteJson("/api/admin/experts/{$booker->uuid}")->assertStatus(422);

	expect(User::find($teacher->id))->not->toBeNull()
		->and(User::find($booker->id))->not->toBeNull();
});

it('refuses to delete your own account', function () {
	$this->admin->syncRoles([Role::Admin, Role::Expert]);

	$this->actingAs($this->admin)->deleteJson("/api/admin/experts/{$this->admin->uuid}")->assertStatus(422);
});

it('takes portraits on the expert, the first as the teaser', function () {
	Storage::fake('public');
	$expert = expertAccount();

	$this->actingAs($this->admin)
		->postJson("/api/admin/experts/{$expert->uuid}/media", ['file' => UploadedFile::fake()->image('portrait.jpg', 1200, 1200)])
		->assertCreated()
		->assertJsonPath('data.role', 'teaser');

	expect($this->getJson("/api/admin/experts/{$expert->uuid}/media")->json('data'))->toHaveCount(1)
		->and($expert->media()->count())->toBe(1);
});

it('starts a new expert in a country the form offers', function () {
	$schema = $this->actingAs($this->admin)->getJson('/api/admin/forms/expert')->json('data');
	$country = collect($schema['fields'])->firstWhere('name', 'country');

	expect(array_column($country['options'], 'value'))->toContain($schema['defaults']['country']);
});

it('has an address the admin changes confirmed by the expert', function () {
	$expert = expertAccount(user: ['email' => 'alt@example.test']);
	Mail::fake();

	$this->actingAs($this->admin)->putJson("/api/admin/experts/{$expert->uuid}", expertPayload(['email' => 'neu@example.test']))
		->assertOk()
		->assertJsonPath('data.email_verified', false);

	Mail::assertQueued(EmailVerification::class, fn ($mail) => $mail->hasTo('neu@example.test'));
});
