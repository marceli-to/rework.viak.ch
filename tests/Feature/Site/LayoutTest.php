<?php

declare(strict_types=1);

use App\Models\Course;

/**
 * Head tags legacy has none of, checked against production 2026-09-18
 * ([[00-foundation]]).
 */
it('emits a canonical tag on the host the site is indexed under', function () {
	$this->get('/de')
		->assertOk()
		->assertSee('<link rel="canonical" href="https://visualisierungs-akademie.ch/de">', false);
});

it('emits hreflang for every locale that has URLs', function () {
	$this->get('/de')->assertSee('hreflang="de"', false);
});

/** A dead render-blocking request on every page of the live site. */
it('does not load the dead neuzeit-grotesk Typekit kit', function () {
	$this->get('/de')->assertDontSee('kcs4ept', false);
});

it('ships Alpine on the public site and no Vue', function () {
	// Vite hashes the filenames, so match the entry rather than the asset:
	// `assets/site-*.js` is the Alpine bundle, `assets/app-*.js` is the Vue SPA.
	$html = $this->get('/de')->getContent();

	expect($html)->toMatch('#assets/site-[^"]+\.js#')
		->not->toMatch('#assets/app-[^"]+\.js#');
});

it('shows the basket count from the shared store', function () {
	$this->get('/de')->assertSee('$store.basket.count', false);
});

it('renders a course page through the layout', function () {
	Course::factory()->create([
		'title' => ['de' => 'Rhino Einstiegskurs'],
		'slug' => ['de' => 'rhino-einstiegskurs'],
		'publish' => true,
	]);

	$this->get('/de/kurs/rhino-einstiegskurs')
		->assertOk()
		->assertSee('Rhino Einstiegskurs')
		->assertSee('rel="canonical"', false);
});

/**
 * The `facts` repeater holds editor HTML, like the description fields. Escaped
 * with `{{ }}` it renders literal `<strong>` tags on the page — which is what
 * the ported rows contain, and what the browser showed.
 */
it('renders the facts block as HTML, not as escaped tags', function () {
	Course::factory()->create([
		'slug' => ['de' => 'mit-fakten'],
		'publish' => true,
		'facts' => [['de' => '<strong>Voraussetzung:</strong> keine']],
	]);

	$this->get('/de/kurs/mit-fakten')
		->assertOk()
		->assertSee('<strong>Voraussetzung:</strong>', false)
		->assertDontSee('&lt;strong&gt;', false);
});

/**
 * `page • Visualisierungs-Akademie`, as legacy composes it from
 * `config('seo.title')`.
 */
it('titles a page with its name and the site name', function () {
	$this->get('/de')->assertSee('<title>Home • Visualisierungs-Akademie</title>', false);
	$this->get('/de/kurse')->assertSee('<title>Kurse • Visualisierungs-Akademie</title>', false);
});

/**
 * The path already begins with the locale, so prepending it again emitted
 * `/de/de` on every page — which pointed every alternate at a 404.
 */
it('does not repeat the locale in an alternate link', function () {
	$this->get('/de/kurse')
		->assertSee('hreflang="de" href="https://visualisierungs-akademie.ch/de/kurse"', false)
		->assertDontSee('/de/de', false);
});

it('carries the head tags the live site sends', function () {
	$html = $this->get('/de')->getContent();

	foreach ([
		'name="keywords"',
		'property="og:image"',
		'property="og:site_name" content="Visualisierungs-Akademie"',
		'name="theme-color"',
		'name="msapplication-TileColor"',
		'name="format-detection" content="telephone=no"',
	] as $tag) {
		expect($html)->toContain($tag);
	}
});

/** `og:title` carries the full title on the live site, not the page name alone. */
it('gives og:title the same string as the title tag', function () {
	$this->get('/de/kurse')
		->assertSee('property="og:title" content="Kurse • Visualisierungs-Akademie"', false);
});

it('lets a course override the description and keywords', function () {
	Course::factory()->create([
		'slug' => ['de' => 'mit-seo'],
		'publish' => true,
		'seo_description' => ['de' => 'Eigene Beschreibung.'],
		'seo_tags' => ['de' => 'eigene, stichworte'],
	]);

	$this->get('/de/kurs/mit-seo')
		->assertSee('name="description" content="Eigene Beschreibung."', false)
		->assertSee('name="keywords" content="eigene, stichworte"', false);
});

/**
 * Legacy's `%word-break` is `word-break` **plus `hyphens: auto`**. Without the
 * hyphens a long German compound breaks mid-word with no hyphen —
 * "Architekturvisualisierun|g" instead of "Architekturvisualisie-rung".
 */
it('hyphenates long words in a card heading', function () {
	Course::factory()->create([
		'title' => ['de' => 'Geheimnisse der Architekturvisualisierung'],
		'slug' => ['de' => 'lang'],
		'publish' => true,
	]);

	$this->get('/de/kurse')->assertSee('hyphens-auto', false);
});

/**
 * The gutter steps: 16px on phones, 32px from 700px, then a centred 1100px
 * column whose 1068px of content sits inside its own padding.
 */
it('steps the container gutter the way the design does', function () {
	$this->get('/de')
		->assertSee('px-16', false)
		->assertSee('sm:px-32', false)
		->assertSee('lg:max-w-[1100px]', false);
});

/**
 * Legacy's normalize gives `button` and every button-ish `input`
 * `cursor: pointer`, and the disabled ones `cursor: default`
 * (`helpers/_normalize.scss:305`). **Tailwind 4's preflight does the opposite**,
 * which is why every button here felt inert until 2026-09-21.
 *
 * A base rule rather than `cursor-pointer` per call site: it is a reset, not a
 * decision, and it has to hold for the Vue dashboard as well.
 */
it('gives buttons the pointer cursor Tailwind 4 takes away', function () {
	$css = file_get_contents(resource_path('css/app.css'));

	expect($css)
		->toContain("button:not(:disabled),\n\t[role='button']:not(:disabled) {")
		->toContain('@apply cursor-pointer;');
});
