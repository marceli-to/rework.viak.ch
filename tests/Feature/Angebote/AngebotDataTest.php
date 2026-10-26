<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\Manufacturer;
use App\Models\Project;
use App\Models\Software;
use App\Models\User;

/**
 * What the Angebot pages will draw, built before their layout
 * ([[14-angebote]]): a licence's features, the note for a licence sold
 * elsewhere, the Vorhaben chips and *Kurs ab CHF*.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->software = Software::create(['title' => ['de' => 'Rhinoceros'], 'order' => 1, 'publish' => true]);
});

function rhinoCourse(Software $software, array $attributes = []): Course
{
	$course = Course::factory()->create(['fee' => '949.00', ...$attributes]);
	$course->software()->attach($software);

	return $course;
}

it('keeps a licence product\'s features, one per line, blank lines dropped', function () {
	$maker = Manufacturer::create(['title' => ['de' => 'McNeel'], 'order' => 1, 'publish' => true]);

	$uuid = $this->actingAs($this->admin)->postJson('/api/admin/licences', [
		'software' => $this->software->uuid, 'manufacturer' => $maker->uuid, 'title' => 'Rhino 8',
		'description' => '', 'features' => "Kauflizenz, unbefristet\n\n  Windows und Mac \r\nUpdates innerhalb der Version\n",
		'hosts' => [], 'three_years_on_request' => false, 'publish' => true,
	])->assertCreated()->json('data.uuid');

	$product = LicenceProduct::query()->where('uuid', $uuid)->first();

	expect($product->featureList())->toBe(['Kauflizenz, unbefristet', 'Windows und Mac', 'Updates innerhalb der Version'])
		->and($this->getJson("/api/admin/licences/{$uuid}")->json('data.features'))->toContain('Windows und Mac')
		->and(LicenceProduct::factory()->create()->featureList())->toBe([]);
});

it('keeps the note for a licence VIAK does not sell', function () {
	$form = $this->actingAs($this->admin)->getJson("/api/admin/software/{$this->software->uuid}")->json('data');
	expect($form['licence_note'])->toBe('');

	$this->putJson("/api/admin/software/{$this->software->uuid}", [...$form, 'licence_note' => ' Lizenz direkt bei Adobe '])
		->assertOk()
		->assertJsonPath('data.licence_note', 'Lizenz direkt bei Adobe');

	$this->putJson("/api/admin/software/{$this->software->uuid}", [...$form, 'licence_note' => ''])->assertOk();
	expect($this->software->refresh()->getTranslation('licence_note', 'de', false))->toBeEmpty();
});

it('lists the published Vorhaben a software is picked for, in their order', function () {
	$second = Project::factory()->create(['title' => ['de' => 'Räume visualisieren'], 'order' => 2]);
	$first = Project::factory()->create(['title' => ['de' => 'Objekte entwerfen'], 'order' => 1]);
	$hidden = Project::factory()->create(['title' => ['de' => 'Bilder gestalten'], 'publish' => false]);
	foreach ([$second, $first, $hidden] as $project) {
		$project->software()->attach($this->software, ['order' => 0]);
	}

	expect($this->software->projects->map->getTranslation('title', 'de')->all())->toBe(['Objekte entwerfen', 'Räume visualisieren']);
});

it('says Kurs ab the cheapest bookable Veranstaltung, and nothing without one', function () {
	expect($this->software->courseFromPrice())->toBeNull();

	$course = rhinoCourse($this->software);
	expect($this->software->courseFromPrice())->toBeNull(); // a course with no date

	Event::factory()->for($course)->create();
	Event::factory()->for($course)->create(['fee' => '590.00', 'date' => now()->addMonths(2)->toDateString()]);
	Event::factory()->for($course)->in(EventState::Cancelled)->create(['fee' => '100.00']);
	Event::factory()->for($course)->past()->create(['fee' => '200.00']);
	Event::factory()->for($course)->create(['fee' => '300.00', 'publish' => false]);
	Event::factory()->for($course)->create(['free_of_charge' => true]);
	Event::factory()->for(rhinoCourse($this->software, ['publish' => false]))->create(['fee' => '50.00']);
	Event::factory()->for(Course::factory()->create(['fee' => '10.00']))->create();

	expect($this->software->courseFromPrice())->toBe('590.00');
});
