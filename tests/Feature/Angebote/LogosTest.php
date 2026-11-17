<?php

declare(strict_types=1);

use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * **The Angebote' logos** (Oliver, 2026-11-17, [[14-angebote]] *Logos*):
 * uploaded in *Einstellungen*, imported from `database/data/logos`, shown in
 * the lists; none where there is none.
 */
beforeEach(function () {
	Storage::fake('public');
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

function onSiteAngebot(string $title): Software
{
	$software = Software::create(['title' => ['de' => $title], 'publish' => true, 'order' => 0]);
	LicenceVariant::factory()->for(LicenceProduct::factory()->for($software)->state(['title' => ['de' => $title.' 9']]), 'product')->create();

	return $software;
}

it('takes a logo in the dashboard, replaces it, and removes it with its file', function () {
	$software = Software::create(['title' => ['de' => 'Rhinoceros']]);

	$first = $this->actingAs($this->admin)
		->post("/api/admin/software/{$software->uuid}/logo", ['file' => UploadedFile::fake()->image('rhino.png', 250, 250)], ['Accept' => 'application/json'])
		->assertOk()->json('data.logo_url');
	$file = basename($first);
	Storage::disk('public')->assertExists("logos/{$file}");

	$second = $this->post("/api/admin/software/{$software->uuid}/logo", ['file' => UploadedFile::fake()->image('rhino-neu.png', 250, 250)], ['Accept' => 'application/json'])
		->json('data.logo_url');
	expect($second)->not->toBe($first);
	Storage::disk('public')->assertMissing("logos/{$file}");

	$this->deleteJson("/api/admin/software/{$software->uuid}/logo")->assertOk()->assertJsonPath('data.logo_url', null);
	Storage::disk('public')->assertMissing('logos/'.basename($second));

	$this->actingAs(User::factory()->expert()->create())->deleteJson("/api/admin/software/{$software->uuid}/logo")->assertForbidden();
});

it('refuses a file that is no picture', function () {
	$software = Software::create(['title' => ['de' => 'Rhinoceros']]);

	$this->actingAs($this->admin)
		->post("/api/admin/software/{$software->uuid}/logo", ['file' => UploadedFile::fake()->create('rhino.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
		->assertJsonValidationErrors('file');
});

it('imports the logos by slug and keeps one already set', function () {
	$rhino = Software::create(['title' => ['de' => 'Rhinoceros']]);
	$vray = Software::create(['title' => ['de' => 'V-Ray'], 'logo' => 'eigenes.png']);

	$this->artisan('catalogue:logos')->assertSuccessful();

	expect($rhino->fresh()->logo)->toStartWith('rhinoceros-')
		->and($vray->fresh()->logo)->toBe('eigenes.png');
	Storage::disk('public')->assertExists('logos/'.$rhino->fresh()->logo);
});

it('shows the logo on the tiles, the page and in the search, and nothing without one', function () {
	$rhino = onSiteAngebot('Rhinoceros');
	$rhino->update(['logo' => 'rhinoceros-abc.png']);
	onSiteAngebot('Lands Design');

	$this->get('/de/angebote')->assertSee('/storage/logos/rhinoceros-abc.png', false);
	$this->get('/de/angebote/rhinoceros')->assertSee('/storage/logos/rhinoceros-abc.png', false);
	$this->get('/de/angebote/software/rhinoceros')->assertSee('/storage/logos/rhinoceros-abc.png', false);
	$this->get('/de/angebote/lands-design')->assertOk()->assertDontSee('/storage/logos/', false);
});
