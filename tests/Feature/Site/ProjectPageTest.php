<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Project;

/**
 * The Vorhaben ([[04-content]]): a page each, title, text and the courses
 * picked for it, and the tiles on the homepage that lead there.
 */
it('renders the title, lead and text, and the picked courses in their order', function () {
	$project = Project::factory()->create([
		'title' => ['de' => 'Räume visualisieren'],
		'slug' => ['de' => 'raeume-visualisieren'],
		'lead' => ['de' => 'Ein Projekt überzeugend zeigen.'],
		'text' => ['de' => '<p>Für Architekt:innen.</p>'],
	]);
	$project->courses()->attach(Course::factory()->create(['title' => ['de' => 'Lumion Einstiegskurs']]), ['order' => 2]);
	$project->courses()->attach(Course::factory()->create(['title' => ['de' => 'Twinmotion Einführungskurs']]), ['order' => 1]);
	$project->courses()->attach(Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf']]), ['order' => 3]);

	$this->get('/de/vorhaben/raeume-visualisieren')
		->assertOk()
		->assertSee('<title>Räume visualisieren • Visualisierungs-Akademie</title>', false)
		->assertSeeInOrder(['Räume visualisieren', 'Ein Projekt überzeugend zeigen.', 'Für Architekt:innen.', 'Twinmotion Einführungskurs', 'Lumion Einstiegskurs'])
		->assertDontSee('Entwurf');
});

it('is not there unpublished, or under another slug', function () {
	Project::factory()->create(['slug' => ['de' => 'entwurf'], 'publish' => false]);

	$this->get('/de/vorhaben/entwurf')->assertNotFound();
	$this->get('/de/vorhaben/gibt-es-nicht')->assertNotFound();
});

it('shows the published Vorhaben as tiles on the homepage, in their order', function () {
	Project::factory()->create(['title' => ['de' => 'Objekte entwerfen'], 'slug' => ['de' => 'objekte-entwerfen'], 'teaser' => ['de' => 'Rhino, Grasshopper'], 'order' => 2]);
	Project::factory()->create(['title' => ['de' => 'Räume visualisieren'], 'slug' => ['de' => 'raeume-visualisieren'], 'order' => 1]);
	Project::factory()->create(['title' => ['de' => 'Mit KI gestalten'], 'publish' => false]);

	$this->get('/de')
		->assertOk()
		->assertSeeInOrder(['Was möchtest du machen?', 'href="/de/vorhaben/raeume-visualisieren"', 'Räume visualisieren', 'href="/de/vorhaben/objekte-entwerfen"', 'Objekte entwerfen', 'Rhino, Grasshopper'], false)
		->assertDontSee('Mit KI gestalten');
});

it('leaves the tiles out until a Vorhaben is published', function () {
	$this->get('/de')->assertOk()->assertDontSee('Was möchtest du machen?');
});
