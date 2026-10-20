<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Event;
use App\Models\LicenceProduct;
use App\Models\LicenceVariant;
use App\Models\Recommendation;
use App\Models\Software;
use App\Models\User;
use App\Support\Recommendations;
use Illuminate\Support\Str;

/**
 * *Das könnte dich auch interessieren* (client, 2026-10-20): picked in the
 * software and course forms, shown under the software's page and in the
 * basket ([[Recommendations]]).
 */
function onSiteSoftware(string $title): Software
{
	$software = Software::create(['title' => ['de' => $title], 'order' => 0, 'publish' => true]);
	$product = LicenceProduct::factory()->create(['software_id' => $software->id, 'title' => ['de' => $title.' Pro']]);
	LicenceVariant::factory()->for($product, 'product')->create(['listed' => true, 'price' => '100.00']);

	return $software;
}

beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->rhino = onSiteSoftware('Rhinoceros');
	$this->vray = onSiteSoftware('V-Ray');
	$this->course = Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]);
});

it('saves the picks from the software form, in order, and gives them back', function () {
	$enscape = onSiteSoftware('Enscape');

	$this->actingAs($this->admin)->putJson("/api/admin/software/{$this->rhino->uuid}", [
		'title' => 'Rhinoceros', 'subtitle' => '', 'short_description' => '', 'full_description' => '', 'information' => '',
		'categories' => [], 'publish' => true, 'featured' => false, 'seo_description' => '', 'seo_tags' => '',
		'recommended_courses' => [$this->course->uuid],
		'recommended_software' => [$enscape->uuid, $this->vray->uuid],
	])->assertOk()
		->assertJsonPath('data.recommended_courses', [$this->course->uuid])
		->assertJsonPath('data.recommended_software', [$enscape->uuid, $this->vray->uuid]);

	expect(Recommendation::query()->orderBy('order')->pluck('item_id')->all())->toBe([$this->course->id, $enscape->id, $this->vray->id]);
});

it('never recommends a software to itself', function () {
	Recommendations::sync($this->rhino, [], [$this->rhino->uuid, $this->vray->uuid]);

	expect(Recommendations::uuids($this->rhino, Software::class))->toBe([$this->vray->uuid]);
});

it('shows the picks under the software page, without the courses it already lists', function () {
	$listed = Course::factory()->create(['title' => ['de' => 'Rhino für Profis']]);
	$listed->software()->attach($this->rhino);
	Recommendations::sync($this->rhino, [$listed->uuid, $this->course->uuid], [$this->vray->uuid]);

	$page = $this->get('/de/software/rhinoceros')->assertOk()
		->assertSee('Das könnte dich auch interessieren')
		->assertSeeInOrder(['Das könnte dich auch interessieren', 'Rhino Einstiegskurs', 'V-Ray']);

	$section = Str::before(Str::after($page->getContent(), 'Das könnte dich auch interessieren'), '</section>');
	expect($section)->toContain('Rhino Einstiegskurs')->not->toContain('Rhino für Profis');
});

it('shows nothing where nothing was picked, or nothing published', function () {
	$this->get('/de/software/rhinoceros')->assertOk()->assertDontSee('Das könnte dich auch interessieren');

	Recommendations::sync($this->rhino, [Course::factory()->unpublished()->create()->uuid], []);
	$this->get('/de/software/rhinoceros')->assertOk()->assertDontSee('Das könnte dich auch interessieren');
});

it('recommends in the basket what was picked for its contents, leaving out what is in it', function () {
	Recommendations::sync($this->rhino, [$this->course->uuid], [$this->vray->uuid]);
	$variant = $this->rhino->products->first()->variants->first();
	$vrayVariant = $this->vray->products->first()->variants->first();

	$this->postJson('/api/basket/recommendations', ['licences' => [['variant' => $variant->uuid]]])
		->assertOk()
		->assertJsonPath('html', fn (string $html) => str_contains($html, 'Rhino Einstiegskurs') && str_contains($html, 'V-Ray'));

	$this->postJson('/api/basket/recommendations', ['licences' => [['variant' => $variant->uuid], ['variant' => $vrayVariant->uuid]]])
		->assertJsonPath('html', fn (string $html) => str_contains($html, 'Rhino Einstiegskurs') && ! str_contains($html, '>V-Ray<'));

	$this->postJson('/api/basket/recommendations', ['items' => []])->assertJsonPath('html', '');
});

it('recommends from a course in the basket, picked in the course form', function () {
	$event = Event::factory()->for($this->course)->create();
	Recommendations::sync($this->course, [], [$this->rhino->uuid]);

	$this->postJson('/api/basket/recommendations', ['items' => [['event' => $event->uuid]]])
		->assertJsonPath('html', fn (string $html) => str_contains($html, 'Rhinoceros'));
});
