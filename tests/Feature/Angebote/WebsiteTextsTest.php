<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Software;
use App\Support\Slug;

/**
 * The client's website texts (#70, answered 2026-11-06, [[14-angebote]] *The
 * website texts*): new optional texts on the Angebot page, *Was ist …?* as a
 * page of its own, a fourth course fact, and the docs' categories.
 */
function rhinoWithTexts(array $attributes = []): Software
{
	$rhino = Software::create([
		'title' => ['de' => 'Rhinoceros'], 'slug' => Slug::forTitles('Rhinoceros'), 'order' => 0, 'publish' => true,
		'short_description' => ['de' => '<p>Hochpräzise 3D-Modelliersoftware.</p>'],
		'full_description' => ['de' => '<p>Rhinoceros, kurz Rhino, ist eine CAD-Software.</p><p><strong>Woher kommt Rhino?</strong></p>'],
		...$attributes,
	]);
	Course::factory()->create(['publish' => true])->software()->attach($rhino);

	return $rhino;
}

it('shows the texts on the Angebot page, each only when filled', function () {
	rhinoWithTexts([
		'audiences' => ['de' => 'Yachtdesign, Industrial Design'],
		'intro' => ['de' => 'Wir bieten Schulungen an:'],
		'courses_intro' => ['de' => 'Lerne Rhino in der Praxis.'],
		'quote' => ['de' => 'Beides aus einer Hand.'],
	]);

	$this->get('/de/angebote/rhinoceros')->assertOk()
		->assertSeeInOrder(['Hochpräzise 3D-Modelliersoftware.', 'Zielgruppen:', 'Yachtdesign, Industrial Design', 'Wir bieten Schulungen an:', 'Was ist Rhinoceros?'])
		->assertSeeInOrder(['Kurse', 'Lerne Rhino in der Praxis.'])
		->assertSee('Beides aus einer Hand.')
		->assertSee('/de/angebote/rhinoceros/was-ist-rhinoceros', false)
		// The text moved to its own page; the collapsible went.
		->assertDontSee('Mehr über')
		->assertDontSee('kurz Rhino, ist eine CAD-Software');
});

it('has no link to Was ist without the text, and no page either', function () {
	rhinoWithTexts(['full_description' => ['de' => '']]);

	$this->get('/de/angebote/rhinoceros')->assertOk()->assertDontSee('Was ist Rhinoceros?');
	$this->get('/de/angebote/rhinoceros/was-ist-rhinoceros')->assertNotFound();
});

it('draws Was ist as a page of its own, with the way back to the Angebot', function () {
	rhinoWithTexts();

	$this->get('/de/angebote/rhinoceros/was-ist-rhinoceros')->assertOk()
		->assertSee('<title>Was ist Rhinoceros?', false)
		->assertSeeInOrder(['Was ist Rhinoceros?', 'Rhinoceros, kurz Rhino, ist eine CAD-Software.', 'Woher kommt Rhino?'])
		->assertSee('href="/de/angebote/rhinoceros"', false)
		->assertSee('content="Rhinoceros, kurz Rhino, ist eine CAD-Software. Woher kommt Rhino?"', false);
});

it('sends a Was ist URL with the wrong name to the right one, and an unknown Angebot nowhere', function () {
	rhinoWithTexts();

	$this->get('/de/angebote/rhinoceros/was-ist-rhino')->assertRedirect('/de/angebote/rhinoceros/was-ist-rhinoceros')->assertStatus(301);
	$this->get('/de/angebote/v-ray/was-ist-v-ray')->assertNotFound();
});
