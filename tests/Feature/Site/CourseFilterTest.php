<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Language;
use App\Models\Level;
use App\Models\Software;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;

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
 * assert: the same uuids reach the browser in `data-facets` as the ones
 * `App\Support\CourseFilter` matched on, and the initial `hidden` is the one
 * `matches()` would compute. The behaviour on top of it was checked in a
 * browser.
 */
function course(string $slug, array $terms = [], bool $online = false): Course
{
	$course = Course::factory()->create([
		'slug' => ['de' => $slug],
		'publish' => true,
		'online' => $online,
	]);

	foreach ($terms as $relation => $term) {
		$course->{$relation}()->attach($term->id);
	}

	return $course;
}

/** The class Blade put on an element, as opposed to the one Alpine binds. */
function staticClass(string $html, string $binding): string
{
	preg_match('#<(?:div|article)[^>]*class="([^"]*)"[^>]*'.preg_quote($binding, '#').'#', $html, $matched);

	return $matched[1] ?? 'not found';
}

/**
 * Every `data-facets` on the page, decoded.
 *
 * Blade escapes the quotes, the parser hands them back and `JSON.parse` reads
 * them — so these assert on what a card actually carries, not on its spelling.
 *
 * @return array<int, array<string, string[]>>
 */
function facets(string $html): Collection
{
	preg_match_all('#data-facets="([^"]*)"#', $html, $found);

	return collect($found[1])->map(fn (string $raw) => json_decode(html_entity_decode($raw), true));
}

/** What `courseFilter()` is handed, decoded the same way. @return array<string, string> */
function seed(string $html): array
{
	preg_match('#courseFilter\((\{.*?\})\)#', html_entity_decode($html), $matched);

	return json_decode($matched[1] ?? '', true) ?? [];
}

beforeEach(function () {
	$this->threeD = Category::create(['title' => ['de' => '3D-Software'], 'publish' => true]);
	$this->image = Category::create(['title' => ['de' => 'Bildgestaltung & Handwerk'], 'publish' => true]);

	$this->rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'publish' => true]);
	$this->blender = Software::create(['title' => ['de' => 'Blender'], 'publish' => true]);
	$this->beginner = Level::create(['title' => ['de' => 'Einsteiger'], 'publish' => true]);
	$this->german = Language::create(['title' => ['de' => 'deutsch'], 'publish' => true]);
	$this->rendering = Tag::create(['title' => ['de' => 'Rendering'], 'publish' => true]);

	$this->modelling = course('modellieren', [
		'categories' => $this->threeD,
		'software' => $this->rhino,
		'levels' => $this->beginner,
		'languages' => $this->german,
		'tags' => $this->rendering,
	]);

	$this->animation = course('animieren', ['categories' => $this->threeD, 'software' => $this->blender], online: true);
	$this->drawing = course('zeichnen', ['categories' => $this->image]);

	// The expert filter matches whoever teaches an upcoming, published event.
	$this->expert = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
	$event = Event::factory()->create(['course_id' => $this->modelling->id, 'publish' => true, 'date' => now()->addMonth()]);
	$event->experts()->attach($this->expert->id);
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
	$hidden = array_filter($articles[0], fn (string $tag) => str_contains($tag, 'col-span-6 hidden'));

	expect($hidden)->toHaveCount(2);
});

it('hides nothing when no filter is set', function () {
	expect($this->get('/de/kurse')->getContent())->not->toContain('col-span-6 hidden');
});

/**
 * `span-6` with no breakpoint prefix in `frontend/filter/Index.vue`, so a phone
 * gets two columns as well. An earlier pass read it as one column below `sm`.
 */
it('puts two cards in a row at every width', function () {
	$html = $this->get('/de/kurse')->getContent();

	expect($html)
		->toContain('col-span-6')
		->not->toContain('col-span-12 sm:col-span-6');
});

/**
 * `grid-gap: $space-4x`, `$space-10x` from `bp-md` — on both axes, not just
 * one. The header keeps `gap-x` only, because legacy gives it
 * `grid-column-gap` rather than `grid-gap`.
 */
it('grows the grid gap on both axes at the wide breakpoint', function () {
	$html = $this->get('/de/kurse')->getContent();
	$main = mb_substr($html, mb_strpos($html, '<main'));

	expect(substr_count($main, 'grid grid-cols-12 gap-16 lg:gap-40'))->toBe(2)
		->and($main)->not->toContain('lg:gap-x-40');
});

it('gives each card every attribute it can be filtered by', function () {
	$html = $this->get('/de/kurse')->getContent();
	$facets = facets($html);

	expect($facets)->toHaveCount(3);

	// The drawing course carries a category and nothing else.
	expect($facets->firstWhere('category.0', $this->image->uuid))
		->toMatchArray(['location' => ['offline'], 'software' => [], 'expert' => []]);

	// Location is not a taxonomy — it is the course's own flag, both ways round.
	expect($facets->pluck('location')->flatten()->unique()->sort()->values()->all())
		->toBe(['offline', 'online']);

	// And the expert comes off the upcoming event, not off the course.
	expect($facets->firstWhere('tag.0', $this->rendering->uuid))
		->toMatchArray(['expert' => [$this->expert->uuid], 'level' => [$this->beginner->uuid]]);

	expect($html)->toContain(':class="{ hidden: !matches($el) }"');
});

it('seeds Alpine with what the server filtered by, so the first paint agrees', function () {
	expect(seed($this->get("/de/kurse?level={$this->beginner->uuid}")->getContent()))
		->toMatchArray(['level' => $this->beginner->uuid, 'software' => '', 'category' => '']);

	// Unset is the empty string, never null — six of the seven are selects.
	expect(array_values(seed($this->get('/de/kurse')->getContent())))->each->toBe('');
});

it('shows the empty state only when nothing matches', function () {
	$unused = Software::create(['title' => ['de' => 'Cinema 4D'], 'publish' => true]);

	// Rendered but hidden while there are results, so Alpine has it to reveal.
	expect(staticClass($this->get('/de/kurse')->getContent(), ':class="{ hidden: count > 0 }"'))->toBe('hidden');

	expect(staticClass($this->get("/de/kurse?software={$unused->uuid}")->getContent(), ':class="{ hidden: count > 0 }"'))->toBe('');
});

it('counts the matches on the phone panel’s Anzeigen button, as legacy does', function () {
	// One text node, or the flex container eats the space before the bracket.
	$this->get('/de/kurse')->assertSee('>Anzeigen (3)</span>', false);
	$this->get("/de/kurse?software={$this->rhino->uuid}")->assertSee('>Anzeigen (1)</span>', false);
});

/**
 * The panel legacy draws: one list, the three categories as links and the other
 * six as selects, with 40px of air before Ort.
 */
it('draws the categories as links and the other six attributes as selects', function () {
	$html = $this->get('/de/kurse')->getContent();

	expect(substr_count($html, "toggle('category'"))->toBe(2)
		->and(substr_count($html, '<select'))->toBe(6);

	foreach (['Ort', 'Software', 'Level', 'Sprache', 'Experte', 'Tags'] as $placeholder) {
		expect($html)->toContain('<option value="">'.$placeholder.'</option>');
	}

	// The single rule on top, a rule under each item, and the gap that separates
	// the two halves — carried by Ort, the first select.
	expect($html)
		->toContain('<ul class="border-t border-gray-400">')
		->toContain('mt-40');
});

it('offers only the terms a course on the page actually carries', function () {
	$orphan = Tag::create(['title' => ['de' => 'Nie verwendet'], 'publish' => true]);
	Software::create(['title' => ['de' => 'Auch nie'], 'publish' => true]);

	$html = $this->get('/de/kurse')->getContent();

	expect($html)
		->toContain('>Rendering</option>')
		->not->toContain('>Nie verwendet</option>')
		->not->toContain('>Auch nie</option>')
		->and($orphan->exists)->toBeTrue();
});

it('lists the experts teaching an upcoming event', function () {
	$idle = User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);

	expect($this->get('/de/kurse')->getContent())
		->toContain('>Ada Lovelace</option>')
		->not->toContain('>Grace Hopper</option>')
		->and($idle->exists)->toBeTrue();
});

/**
 * The half that works without JavaScript. A category is a real link to the view
 * it selects; a select cannot be, so the six of them submit the form instead.
 */
it('makes each category a link that selects it, and the active one a link that clears it', function () {
	$html = $this->get("/de/kurse?category={$this->threeD->uuid}")->getContent();

	expect($html)
		// The other category is not selected, so its link selects it.
		->toContain('href="'.url('/de/kurse').'?category='.$this->image->uuid.'"')
		// This one is, so its own link clears it — with no bare `?` left over.
		->toContain('href="'.url('/de/kurse').'"')
		->toContain("toggle('category', '{$this->threeD->uuid}')");
});

it('lets the selects be submitted without JavaScript, keeping the category', function () {
	$html = $this->get("/de/kurse?category={$this->threeD->uuid}")->getContent();

	expect($html)
		->toContain('<form method="get" action="'.url('/de/kurse').'"')
		->toContain('<button type="submit" class="sr-only">')
		// Category is chosen by link, so a submit would otherwise drop it.
		->toContain('<input type="hidden" name="category" value="'.$this->threeD->uuid.'"');
});

it('combines the attributes rather than replacing one with another', function () {
	// Rhino is on the 3D course; Blender is on the other 3D course.
	$this->get("/de/kurse?category={$this->threeD->uuid}&software={$this->rhino->uuid}")
		->assertSee('>Anzeigen (1)</span>', false);

	// A pairing no course carries. Legacy drops the count rather than showing
	// a zero, so the label stands alone.
	$this->get("/de/kurse?category={$this->image->uuid}&software={$this->rhino->uuid}")
		->assertSee('>Anzeigen</span>', false);

	// And a link built from one of them carries the other along.
	expect($this->get("/de/kurse?software={$this->rhino->uuid}")->getContent())
		->toContain('software='.$this->rhino->uuid.'&amp;category='.$this->threeD->uuid);
});

it('marks the active category in the markup and in the binding', function () {
	$html = $this->get("/de/kurse?category={$this->threeD->uuid}")->getContent();

	expect($html)
		->toContain('class="block w-full text-lg leading-[1.3] hover:text-teal font-bold text-gray-400"')
		->toContain("{ 'font-bold text-gray-400': selected.category === '{$this->threeD->uuid}' }")
		// A select marks itself.
		->toContain('<option value="'.$this->rhino->uuid.'"');
});

it('preselects the option the query string names', function () {
	$html = $this->get("/de/kurse?level={$this->beginner->uuid}")->getContent();

	expect($html)->toContain('<option value="'.$this->beginner->uuid.'" selected>Einsteiger</option>');
});

/** Legacy renders it unconditionally, and the live page shows it on a bare list. */
it('shows the reset control even with nothing chosen', function () {
	foreach (['/de/kurse', "/de/kurse?tag={$this->rendering->uuid}"] as $url) {
		expect($this->get($url)->getContent())
			->toContain('hover:text-black w-full"')
			->not->toContain(':class="{ hidden: !active }"');
	}

	// With nothing chosen it points at the unfiltered page, so it is inert
	// rather than absent.
	expect($this->get('/de/kurse')->getContent())
		->toContain('href="'.url('/de/kurse').'" 	class="flex min-h-28');
});

it('still leaves unpublished courses out entirely', function () {
	Course::factory()->unpublished()->create(['slug' => ['de' => 'entwurf']]);

	expect(substr_count($this->get('/de/kurse')->getContent(), '<article'))->toBe(3);
});

/**
 * Tailwind pairs a line-height with every `text-*` size, so `text-lg` was
 * setting 1.556 where legacy inherits the body's 1.3 — six select rows an
 * aggregate 6px too tall, and the whole list 4px low under the heading. The
 * type scale means to carry no line-heights at all; see `resources/css/README.md`.
 */
it('pins the filter to legacy’s inherited line height rather than Tailwind’s pairing', function () {
	$html = $this->get('/de/kurse')->getContent();

	expect($html)
		->toContain('<h2 class="mb-32 text-lg leading-[1.3] font-bold">Filter</h2>')
		->toContain('text-lg leading-[1.3] text-black')
		// `min-height: inherit` on legacy's select wrapper.
		->toContain('relative flex min-h-40 w-full items-center py-8');
});
