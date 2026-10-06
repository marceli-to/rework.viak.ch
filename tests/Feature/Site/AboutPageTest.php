<?php

declare(strict_types=1);

use App\Models\ExpertProfile;
use App\Models\Media;
use App\Models\TeamMember;
use App\Models\User;

/**
 * *Über uns* ([[04-content]]): the Über uns text, the experts and the team, the
 * Team mockup's three parts. Who the experts are and in what order is
 * `ExpertPagesTest`'s; this is the page around them.
 */
it('renders the three parts in the mockup’s order, and the nav lights Über uns', function () {
	$expert = User::factory()->expert()->create(['first_name' => 'Remo', 'last_name' => 'Kast']);
	ExpertProfile::factory()->for($expert)->create();
	TeamMember::factory()->create(['name' => 'Nina Muster']);

	$response = $this->get('/de/ueber-uns')
		->assertOk()
		->assertSee('<title>Über uns • Visualisierungs-Akademie</title>', false)
		->assertSeeInOrder(['Seit 20 Jahren dabei und doch brandneu!', 'Experten', 'Remo Kast', 'Team', 'Nina Muster']);

	expect($response->getContent())->toMatch('/<a[^>]*href="\/de\/ueber-uns"[^>]*text-teal/s')
		->and($response->getContent())->not->toContain('href="/de/experten"');
});

it('carries the Über uns text across whole from Kontakt', function () {
	$this->get('/de/ueber-uns')
		->assertSee("Seminare unter dem Titel '2sek Manager'", false)
		->assertSee('als individuelle Einzel- oder Firmenschulung.');
});

it('shows published team members in their order, with what they do', function () {
	TeamMember::factory()->create(['name' => 'Zweite Person', 'role' => ['de' => 'Marketing'], 'order' => 2]);
	TeamMember::factory()->create(['name' => 'Erste Person', 'role' => ['de' => 'Geschäftsleitung'], 'order' => 1]);
	TeamMember::factory()->create(['name' => 'Nicht Publiziert', 'publish' => false]);

	$this->get('/de/ueber-uns')
		->assertSeeInOrder(['Erste Person', 'Geschäftsleitung', 'Zweite Person', 'Marketing'])
		->assertDontSee('Nicht Publiziert');
});

it('leaves the Team block out until someone is published', function () {
	TeamMember::factory()->create(['publish' => false]);

	expect($this->get('/de/ueber-uns')->getContent())->not->toMatch('/>\s*Team\s*</');
});

it('draws a team member’s portrait, or the grey square without one', function () {
	$member = TeamMember::factory()->create(['name' => 'Mit Bild']);
	Media::factory()->for($member, 'mediable')->create(['file' => 'mit-bild.jpg', 'is_teaser' => true]);
	TeamMember::factory()->create(['name' => 'Ohne Bild']);

	$html = $this->get('/de/ueber-uns')->assertSee('mit-bild.jpg')->getContent();

	expect(substr_count($html, 'aspect-square w-full bg-gray-200'))->toBe(1);
});
