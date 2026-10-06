<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * *Seiteninhalte → Team* — `/api/admin/team-members` ([[07-dashboard]]), the
 * people on *Über uns*.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function teamMemberPayload(array $overrides = []): array
{
	return [
		'name' => 'Nina Muster',
		'role' => 'Kursadministration',
		'publish' => true,
		...$overrides,
	];
}

it('keeps the team to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/team-members')->assertForbidden();
	$this->actingAs(User::factory()->expert()->create())->postJson('/api/admin/team-members', teamMemberPayload())->assertForbidden();
});

it('creates one at the end of the list', function () {
	TeamMember::factory()->create(['order' => 4]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/team-members', teamMemberPayload())->assertCreated()->json('data.uuid');

	expect(TeamMember::where('uuid', $uuid)->first())
		->order->toBe(5)
		->name->toBe('Nina Muster')
		->and(TeamMember::where('uuid', $uuid)->first()->getTranslation('role', 'de'))->toBe('Kursadministration');
});

it('sends back exactly what it loads', function () {
	$member = TeamMember::factory()->create();
	$form = $this->actingAs($this->admin)->getJson("/api/admin/team-members/{$member->uuid}")->json('data');

	expect($this->putJson("/api/admin/team-members/{$member->uuid}", $form)->assertOk()->json('data'))->toBe($form);
});

it('writes German and leaves the English where it was', function () {
	$member = TeamMember::factory()->create(['role' => ['de' => 'Alt', 'en' => 'Old']]);

	$this->actingAs($this->admin)->putJson("/api/admin/team-members/{$member->uuid}", teamMemberPayload(['role' => 'Neu']));

	expect($member->refresh()->getTranslation('role', 'en'))->toBe('Old')
		->and($member->getTranslation('role', 'de'))->toBe('Neu');
});

it('asks for the name, in German, and lets the role be empty', function () {
	$this->actingAs($this->admin)
		->postJson('/api/admin/team-members', teamMemberPayload(['name' => '']))
		->assertJsonPath('errors.name.0', 'Name muss ausgefüllt sein.');

	$uuid = $this->postJson('/api/admin/team-members', teamMemberPayload(['role' => '']))->assertCreated()->json('data.uuid');

	expect(TeamMember::where('uuid', $uuid)->first()->getTranslation('role', 'de', false))->toBe('');
});

it('lists them in their order, and saves a new one', function () {
	$a = TeamMember::factory()->create(['order' => 1]);
	$b = TeamMember::factory()->create(['order' => 2]);

	$this->actingAs($this->admin);
	expect(array_column($this->getJson('/api/admin/team-members')->json('data'), 'uuid'))->toBe([$a->uuid, $b->uuid]);

	$this->postJson('/api/admin/team-members/order', ['team_members' => [$b->uuid, $a->uuid]])->assertNoContent();

	expect(array_column($this->getJson('/api/admin/team-members')->json('data'), 'uuid'))->toBe([$b->uuid, $a->uuid]);
});

it('uploads a portrait, which becomes the card’s image', function () {
	Storage::fake('public');
	$member = TeamMember::factory()->create();

	$this->actingAs($this->admin)
		->postJson("/api/admin/team-members/{$member->uuid}/media", ['file' => UploadedFile::fake()->image('portrait.jpg', 1200, 1200)])
		->assertCreated()
		->assertJsonPath('data.role', 'teaser');

	expect($this->getJson("/api/admin/team-members/{$member->uuid}/media")->json('data'))->toHaveCount(1);
});

it('deletes one, portrait and all', function () {
	Storage::fake('public');
	$member = TeamMember::factory()->create();
	$portrait = Media::factory()->for($member, 'mediable')->create();

	$this->actingAs($this->admin)->deleteJson("/api/admin/team-members/{$member->uuid}")->assertNoContent();

	expect(TeamMember::find($member->id))->toBeNull()
		->and(Media::find($portrait->id))->toBeNull();
});

it('serves the form', function () {
	$schema = $this->actingAs($this->admin)->getJson('/api/admin/forms/team-member')->assertOk()->json('data');

	expect(array_column(array_filter($schema['fields'], fn ($field) => isset($field['name'])), 'name'))->toContain('name', 'role');
});
