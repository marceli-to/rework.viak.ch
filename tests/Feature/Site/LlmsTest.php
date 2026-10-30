<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Course;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Software;

/**
 * `/llms.txt` ([[LlmsController]]): Markdown for AI assistants, the rules an
 * answer about VIAK gets wrong easily, and every Angebot under its category
 * as *Alle Angebote* has it, on the canonical host.
 */
beforeEach(function () {
	$this->host = 'https://'.config('site.canonical_host');

	$jewellery = Category::create(['title' => ['de' => 'Schmuck- & Produktdesign'], 'order' => 1, 'publish' => true]);

	$rhino = Software::create(['title' => ['de' => 'Rhinoceros'], 'publish' => true]);
	LicenceVariant::factory()->create(['licence_product_id' => LicenceProduct::factory()->create(['software_id' => $rhino->id])->id]);
	Course::factory()->create()->software()->attach($rhino);
	$rhino->categories()->attach($jewellery);

	Course::factory()->create(['title' => ['de' => 'Urban Sketching'], 'slug' => ['de' => 'urban-sketching']]);
	Course::factory()->unpublished()->create(['title' => ['de' => 'Entwurf'], 'slug' => ['de' => 'entwurf']]);
});

it('serves Markdown with the rules an answer needs', function () {
	$this->get('/llms.txt')
		->assertOk()
		->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
		->assertSee('# Visualisierungs-Akademie', false)
		->assertSee('Kurse sind von der MWST ausgenommen', false)
		->assertSee('exkl. 8.1% MWST', false)
		->assertSee('Computer mieten, für CHF 80', false)
		->assertSee("- [Kontakt]({$this->host}/de/kontakt)", false);
});

it('lists every Angebot under its category, unescaped, and what has none under Weitere', function () {
	$this->get('/llms.txt')
		->assertSeeInOrder([
			'## Schmuck- & Produktdesign',
			"- [Rhinoceros]({$this->host}/de/angebote/rhinoceros): Kurs & Software",
			'## Weitere',
			"- [Urban Sketching]({$this->host}/de/angebote/kurse/urban-sketching): Kurs",
		], false)
		->assertDontSee('&amp;', false)
		->assertDontSee('Entwurf');
});
