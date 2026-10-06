<?php

declare(strict_types=1);

use App\Mail\TrainingEnquiry;
use App\Models\Page;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Firmenschulung (`04-content.md`, #25): legacy's copy at a new URL, an
 * enquiry mailed to the office, and the testimonials picked for it.
 */
beforeEach(function () {
	Mail::fake();
	config(['mail.admin' => 'office@viak.test']);
	RateLimiter::clear('training:127.0.0.1');
});

function trainingPayload(array $overrides = []): array
{
	return [
		'company' => 'Architekturbüro Muster',
		'name' => 'Anna Muster',
		'email' => 'anna@example.test',
		'message' => "Rhino für sechs Leute, im Frühling.\nBei uns im Büro.",
		...$overrides,
	];
}

it('renders at /de/firmenschulung with legacy’s copy, then the enquiry', function () {
	$this->get('/de/firmenschulung')
		->assertOk()
		->assertSee('<title>Firmenschulung • Visualisierungs-Akademie</title>', false)
		->assertSeeInOrder([
			'Individualschulungen für Firmen und Einzelpersonen',
			'Was ist Ihr Thema?<br>Bestimmt finden wir',
			'href="tel:+41435014040"',
			'Anfragen',
			'name="company"', 'name="name"', 'name="email"', 'name="message"',
			'Anfrage senden',
		], false);
});

it('301s legacy’s indexed url to it, and lists it in the sitemap', function () {
	$this->get('/de/individualschulungen')->assertMovedPermanently()->assertRedirect('/de/firmenschulung');

	$this->get('/sitemap.xml')->assertSee('/de/firmenschulung');
});

it('stays out of the nav, and Kontakt and the Kurse filter link to it', function () {
	$html = $this->get('/de/kurse')->assertSee('href="/de/firmenschulung"', false)->getContent();
	$nav = Str::between($html, 'aria-label="Hauptnavigation"', '</nav>');

	expect($nav)->toContain('Kontakt')->not->toContain('/de/firmenschulung');
	$this->get('/de/kontakt')->assertSee('href="/de/firmenschulung"', false)->assertSee('Anfrage für eine Firmenschulung?');
});

it('mails the enquiry to the office only, with the contact person to reply to', function () {
	$this->post('/de/firmenschulung', trainingPayload())
		->assertRedirect('/de/firmenschulung#anfrage')
		->assertSessionHas('training', 'sent');

	Mail::assertQueued(TrainingEnquiry::class, fn (TrainingEnquiry $mail) => $mail->hasTo('office@viak.test')
		&& $mail->hasReplyTo('anna@example.test', 'Anna Muster')
		&& $mail->company === 'Architekturbüro Muster');
	Mail::assertQueuedCount(1);
});

it('renders the mail as a table, the message last and top-aligned', function () {
	$html = (new TrainingEnquiry('Büro <AG>', 'Anna Muster', 'anna@example.test', "eins\nzwei"))->render();

	expect($html)->toContain('Über die Seite Firmenschulung')->not->toContain('Büro <AG>')
		->and($html)->toContain('Büro &lt;AG&gt;')
		->toMatch('/>Nachricht<\/td>\s*<td[^>]*vertical-align: top[^>]*>eins<br>/');
});

it('asks for all four fields, in German, and sends nothing', function () {
	$this->post('/de/firmenschulung', trainingPayload(['company' => '', 'name' => '', 'email' => 'x', 'message' => '']))
		->assertSessionHasErrors([
			'company' => 'Firma muss ausgefüllt sein.',
			'name' => 'Ansprechperson muss ausgefüllt sein.',
			'email' => 'E-Mail muss eine gültige E-Mail-Adresse sein.',
			'message' => 'Nachricht muss ausgefüllt sein.',
		]);

	Mail::assertNothingQueued();
});

it('tells a bot it worked and sends nothing, and takes five an hour', function () {
	$this->post('/de/firmenschulung', trainingPayload(['website' => 'x']))->assertSessionHas('training', 'sent');
	Mail::assertNothingQueued();

	foreach (range(1, 5) as $i) {
		$this->post('/de/firmenschulung', trainingPayload())->assertSessionHasNoErrors();
	}
	$this->post('/de/firmenschulung', trainingPayload())->assertSessionHasErrors('message');
});

it('shows the testimonials picked for it, in their order, published only', function () {
	$page = Page::for('firmenschulung');
	$first = Testimonial::factory()->create(['quote' => ['de' => 'Erstes Zitat'], 'name' => 'Erste Person']);
	$second = Testimonial::factory()->create(['quote' => ['de' => 'Zweites Zitat'], 'name' => 'Zweite Person']);
	$hidden = Testimonial::factory()->create(['quote' => ['de' => 'Verstecktes Zitat'], 'publish' => false]);
	Testimonial::factory()->create(['quote' => ['de' => 'Nicht gewählt']]);

	$page->testimonials()->attach([$second->id => ['order' => 2], $first->id => ['order' => 1], $hidden->id => ['order' => 3]]);

	$this->get('/de/firmenschulung')
		->assertSeeInOrder(['Anfragen', '„Erstes Zitat“', 'Erste Person', '„Zweites Zitat“', 'Zweite Person'], false)
		->assertDontSee('Verstecktes Zitat')
		->assertDontSee('Nicht gewählt');
});

it('shows no testimonial cards until one is picked', function () {
	Testimonial::factory()->create();

	$this->get('/de/firmenschulung')->assertDontSee('<blockquote', false);
});

it('fills in a signed-in visitor’s company, name and address', function () {
	$user = User::factory()->create(['first_name' => 'Remo', 'last_name' => 'Kast', 'company' => 'Kast AG', 'email' => 'remo@example.test']);

	$this->actingAs($user)->get('/de/firmenschulung')
		->assertSee('value="Kast AG"', false)
		->assertSee('value="Remo Kast"', false)
		->assertSee('value="remo@example.test"', false);
});
