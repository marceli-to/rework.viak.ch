<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Software;

/**
 * The course filter ([[09-public-site]]).
 *
 * **The whole catalogue is rendered and the query string decides what is
 * hidden**, so that changing a filter is a class change rather than a
 * navigation — on a phone the filter is a full-screen panel, and a navigation
 * would close it mid-use.
 *
 * Which makes the server's job to render a page that is already correct before
 * Alpine starts, and to agree with it afterwards. That agreement is what these
 * assert: the same uuids reach the browser in `data-facets` as the ones the
 * server matched on, and the initial `hidden` is the one `matches()` would
 * compute. The behaviour on top of it was checked in a browser.
 */
function course(string $slug, ?Software $software = null): Course
{
	$course = Course::factory()->create(['slug' => ['de' => $slug], 'publish' => true]);

	if ($software) {
		$course->software()->attach($software->id);
	}

	return $course;
}

beforeEach(function () {
	$this->rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'publish' => true]);
	$this->blender = Software::create(['title' => ['de' => 'Blender'], 'publish' => true]);

	$this->modelling = course('modellieren', $this->rhino);
	$this->animation = course('animieren', $this->blender);
	$this->drawing = course('zeichnen');
});

it('renders every published course even when the query string filters', function () {
	$html = $this->get("/de/kurse?software={$this->rhino->uuid}")->getContent();

	// Three courses, one filter: legacy would have sent one card.
	expect(substr_count($html, '<article'))->toBe(3);
});

it('hides the courses the query string does not match, and only those', function () {
	$html = $this->get("/de/kurse?software={$this->rhino->uuid}")->getContent();

	preg_match_all('#<article[^>]*>#', $html, $articles);

	// The binding mentions `hidden` on every card, so the server's decision is
	// the `hidden` inside the plain class attribute.
	$hidden = array_filter(
		$articles[0],
		fn (string $tag) => str_contains($tag, 'sm:col-span-6 hidden'),
	);

	expect($hidden)->toHaveCount(2);
});

it('hides nothing when no filter is set', function () {
	$html = $this->get('/de/kurse')->getContent();

	expect($html)->not->toContain('sm:col-span-6 hidden');
});

it('gives each card the attributes it can be filtered by', function () {
	$html = $this->get('/de/kurse')->getContent();

	// Escaped by Blade, decoded by the parser, read back by `JSON.parse`.
	expect($html)
		->toContain('data-facets="{&quot;software&quot;:[&quot;'.$this->rhino->uuid.'&quot;]}"')
		->toContain('data-facets="{&quot;software&quot;:[]}"')
		->toContain(':class="{ hidden: !matches($el) }"');
});

it('seeds Alpine with what the server filtered by, so the first paint agrees', function () {
	$this->get("/de/kurse?software={$this->rhino->uuid}")
		->assertSee("courseFilter({ software: '{$this->rhino->uuid}' })", false);

	$this->get('/de/kurse')->assertSee('courseFilter({ software: null })', false);
});

/** The class Blade put on the empty state, as opposed to the one Alpine binds. */
function emptyState(string $html): string
{
	preg_match('#<div class="([^"]*)" :class="\{ hidden: count > 0 \}">#', $html, $matched);

	return $matched[1] ?? 'the empty state is missing';
}

it('shows the empty state only when nothing matches', function () {
	$unmatched = Software::create(['title' => ['de' => 'Cinema 4D'], 'publish' => true]);

	// Rendered but hidden while there are results, so Alpine has it to reveal.
	expect(emptyState($this->get('/de/kurse')->getContent()))->toBe('hidden');

	expect(emptyState($this->get("/de/kurse?software={$unmatched->uuid}")->getContent()))->toBe('');
});

it('counts the matches on the phone panel’s Anzeigen button, as legacy does', function () {
	$this->get('/de/kurse')->assertSee('>(3)</span>', false);
	$this->get("/de/kurse?software={$this->rhino->uuid}")->assertSee('>(1)</span>', false);
});

/**
 * The half that works without JavaScript. Every control is a real link to the
 * view it selects; the `@click.prevent` beside it is what a browser runs.
 */
it('makes each filter a link that selects it, and the active one a link that clears it', function () {
	$html = $this->get("/de/kurse?software={$this->rhino->uuid}")->getContent();

	expect($html)
		// Blender is not selected, so its link selects it.
		->toContain('href="'.url('/de/kurse').'?software='.$this->blender->uuid.'"')
		// Rhinoceros is, so its own link clears it — with no bare `?` left over.
		->toContain('href="'.url('/de/kurse').'"')
		->toContain("toggle('software', '{$this->rhino->uuid}')");
});

it('marks the active filter in the markup and in the binding', function () {
	$html = $this->get("/de/kurse?software={$this->rhino->uuid}")->getContent();

	expect($html)
		->toContain('class="block w-full text-lg hover:text-teal font-bold text-gray-400"')
		->toContain("{ 'font-bold text-gray-400': selected.software === '{$this->rhino->uuid}' }");
});

it('renders the reset control even with nothing selected, hidden until it applies', function () {
	expect($this->get('/de/kurse')->getContent())
		->toContain('hover:text-black w-full hidden')
		->toContain(':class="{ hidden: !active }"');

	expect($this->get("/de/kurse?software={$this->rhino->uuid}")->getContent())
		->toContain('hover:text-black w-full"');
});

it('still leaves unpublished courses out entirely', function () {
	Course::factory()->unpublished()->create(['slug' => ['de' => 'entwurf']]);

	expect(substr_count($this->get('/de/kurse')->getContent(), '<article'))->toBe(3);
});
