<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The homepage's legacy parts ([[HomeController]]): the intro with its slider
 * (legacy's home hero, on `Page::for('home')`) and the footer with the
 * newsletter form, which reaches no Mailchimp list (#13).
 */
beforeEach(function () {
	RateLimiter::clear('newsletter:127.0.0.1');
});

it('opens with legacy’s intro', function () {
	$this->get('/de')
		->assertOk()
		->assertSeeInOrder(['Ihre Zukunft ist visuell', 'Visualisieren ist die Schlüsselkompetenz der Zukunft.', 'Wir sind führend, wenn es um Visualisierung geht.']);
});

it('slides the homepage’s images in their order, and keeps the Open Graph one for the head', function () {
	$page = Page::for('home');
	Media::factory()->for($page, 'mediable')->create(['file' => 'zweites.jpg', 'sort_order' => 2]);
	Media::factory()->for($page, 'mediable')->create(['file' => 'erstes.jpg', 'sort_order' => 1]);
	Media::factory()->for($page, 'mediable')->create(['file' => 'teilen.jpg', 'is_og' => true]);

	$html = $this->get('/de')
		->assertSee('class="swiper-wrapper"', false)
		->assertSee('<meta property="og:image" content="https://'.config('site.canonical_host').'/storage/uploads/teilen.jpg">', false)
		->getContent();

	$carousel = Str::between($html, 'class="swiper-wrapper"', 'Vorheriges Bild');

	expect($carousel)->toMatch('/erstes\.jpg.*zweites\.jpg/s')
		->not->toContain('teilen.jpg');
});

it('draws one image without the slider, and none at all without images', function () {
	$this->get('/de')->assertDontSee('class="swiper-wrapper"', false);

	Media::factory()->for(Page::for('home'), 'mediable')->create(['file' => 'einzig.jpg']);

	$this->get('/de')->assertSee('einzig.jpg')->assertDontSee('class="swiper-wrapper"', false);
});

it('has legacy’s footer, on the homepage only', function () {
	$this->get('/de')
		->assertSeeInOrder(['Newsletter', 'Regelmässig über neue Kurse und Angebote informiert werden:', 'Abonnieren', 'Kontakt', 'Limmatstrasse 291', 'hallo@visualisierungs-akademie.ch', 'Instagram', 'Facebook']);

	$this->get('/de/kurse')->assertDontSee('Regelmässig über neue Kurse');
});

it('takes a signup and only logs it, nothing goes to Mailchimp', function () {
	Log::spy();

	$this->post('/de/newsletter', ['firstname' => 'Anna', 'name' => 'Muster', 'email' => 'anna@example.test'])
		->assertRedirect('/de#newsletter')
		->assertSessionHas('newsletter', 'sent');

	Log::shouldHaveReceived('info')->once()->withArgs(fn ($message, $context) => str_contains($message, 'not sent to Mailchimp') && $context['email'] === 'anna@example.test');

	$this->followingRedirects()->post('/de/newsletter', ['firstname' => 'Ben', 'name' => 'Muster', 'email' => 'ben@example.test'])
		->assertSee('Wir haben Deine Anmeldung erhalten, vielen Dank.');
});

it('asks for all three, in German, and comes back with the form open', function () {
	$this->post('/de/newsletter', ['firstname' => '', 'name' => '', 'email' => 'x'])
		->assertSessionHasErrors([
			'firstname' => 'Vorname muss ausgefüllt sein.',
			'name' => 'Nachname muss ausgefüllt sein.',
			'email' => 'E-Mail muss eine gültige E-Mail-Adresse sein.',
		]);

	$this->followingRedirects()->post('/de/newsletter', ['firstname' => '', 'name' => 'Muster', 'email' => 'anna@example.test'])
		->assertSee('x-data="{ open: true }"', false);
});

it('tells a bot it worked and logs nothing, and takes five an hour', function () {
	Log::spy();

	$this->post('/de/newsletter', ['firstname' => 'Bot', 'name' => 'Bot', 'email' => 'bot@example.test', 'website' => 'x'])->assertSessionHas('newsletter', 'sent');
	Log::shouldNotHaveReceived('info');

	foreach (range(1, 5) as $i) {
		$this->post('/de/newsletter', ['firstname' => 'Anna', 'name' => 'Muster', 'email' => "anna{$i}@example.test"])->assertSessionHasNoErrors();
	}

	$this->post('/de/newsletter', ['firstname' => 'Anna', 'name' => 'Muster', 'email' => 'anna@example.test'])->assertSessionHasErrors('email');
});

it('lets an admin manage the homepage’s images', function () {
	Storage::fake('public');
	$admin = User::factory()->admin()->create(['email_verified_at' => now()]);

	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/pages/home/media')->assertForbidden();

	$this->actingAs($admin)
		->postJson('/api/admin/pages/home/media', ['file' => UploadedFile::fake()->image('hero.jpg', 1600, 900)])
		->assertCreated();

	expect($this->getJson('/api/admin/pages/home/media')->json('data'))->toHaveCount(1)
		->and(Page::for('home')->media)->toHaveCount(1);

	$this->getJson('/api/admin/pages/gibt-es-nicht/media')->assertNotFound();
});
