<?php

declare(strict_types=1);

use App\Mail\ContactMessage;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * The Kontakt form ([[ContactController]], `Open-Questions.md` #24): mailed to
 * the office only, never stored, and kept from
 * bots by a honeypot and a limit per address.
 */
beforeEach(function () {
	Mail::fake();
	config(['mail.admin' => 'office@viak.test']);
	RateLimiter::clear('contact:127.0.0.1');
});

function contactPayload(array $overrides = []): array
{
	return [
		'name' => 'Anna Muster',
		'email' => 'anna@example.test',
		'message' => "Gibt es den Rhino-Kurs auch im Frühling?\nDanke!",
		...$overrides,
	];
}

it('draws the form on Kontakt, in its own collapsible under the address', function () {
	$this->get('/de/kontakt')
		->assertSeeInOrder(['Get in touch', 'Kontaktformular', 'name="name"', 'name="email"', 'name="message"', 'Nachricht senden', 'Anreise'], false)
		->assertSee('action="/de/kontakt"', false);
});

it('mails the office only, with the sender to reply to', function () {
	$this->post('/de/kontakt', contactPayload())
		->assertRedirect('/de/kontakt#nachricht')
		->assertSessionHas('contact', 'sent');

	Mail::assertQueued(ContactMessage::class, fn (ContactMessage $mail) => $mail->hasTo('office@viak.test')
		&& $mail->hasReplyTo('anna@example.test', 'Anna Muster')
		&& $mail->text === contactPayload()['message']);

	Mail::assertQueuedCount(1);
});

it('renders the message as a row of the table, its line breaks kept and its markup escaped', function () {
	$office = (new ContactMessage('Anna Muster', 'anna@example.test', "Zeile eins\n<b>Zeile zwei</b>"))->render();

	expect($office)->toMatch('/>Nachricht<\/td>\s*<td[^>]*vertical-align: top[^>]*>Zeile eins<br>/')
		->toContain('&lt;b&gt;Zeile zwei&lt;/b&gt;')
		->toContain('anna@example.test');
});

it('says it was sent, on the page it lands on', function () {
	$this->followingRedirects()->post('/de/kontakt', contactPayload())
		->assertSee('Danke, deine Nachricht ist bei uns angekommen.');
});

it('asks for all three fields, in German, and sends nothing', function () {
	$this->post('/de/kontakt', contactPayload(['name' => '', 'email' => 'keine-adresse', 'message' => '']))
		->assertSessionHasErrors([
			'name' => 'Name muss ausgefüllt sein.',
			'email' => 'E-Mail muss eine gültige E-Mail-Adresse sein.',
			'message' => 'Nachricht muss ausgefüllt sein.',
		]);

	Mail::assertNothingQueued();
});

it('tells a bot it worked and sends nothing', function () {
	$this->post('/de/kontakt', contactPayload(['website' => 'https://spam.example']))
		->assertSessionHas('contact', 'sent');

	Mail::assertNothingQueued();
});

it('takes five messages an hour from one address, then refuses', function () {
	foreach (range(1, 5) as $i) {
		$this->post('/de/kontakt', contactPayload())->assertSessionHasNoErrors();
	}

	$this->post('/de/kontakt', contactPayload())->assertSessionHasErrors('message');

	Mail::assertQueuedCount(5);
});

it('fills in a signed-in visitor’s name and address', function () {
	$user = User::factory()->create(['first_name' => 'Remo', 'last_name' => 'Kast', 'email' => 'remo@example.test']);

	$this->actingAs($user)->get('/de/kontakt')
		->assertSee('value="Remo Kast"', false)
		->assertSee('value="remo@example.test"', false);
});

/**
 * Cloudflare Turnstile ([[Turnstile]]): checked only with keys, and then the
 * token must be one Cloudflare vouches for.
 */
function withTurnstile(array $overrides = []): void
{
	config(['services.turnstile' => ['site_key' => 'site-key', 'secret_key' => 'secret-key', 'hostnames' => ['rework.viak.ch.test'], ...$overrides]]);
}

function cloudflareSays(array $answer = []): void
{
	Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'action' => 'contact', 'hostname' => 'rework.viak.ch.test', ...$answer])]);
}

it('leaves Turnstile out without keys, page and check alike', function () {
	Http::fake();

	$this->get('/de/kontakt')->assertDontSee('challenges.cloudflare.com')->assertDontSee('cf-turnstile');
	$this->post('/de/kontakt', contactPayload())->assertSessionHasNoErrors();

	Http::assertNothingSent();
});

it('draws the widget with a site key, once, in German', function () {
	withTurnstile();

	$html = $this->get('/de/kontakt')
		->assertSee('data-sitekey="site-key"', false)
		->assertSee('data-action="contact"', false)
		->assertSee('data-language="de"', false)
		->getContent();

	expect(substr_count($html, 'challenges.cloudflare.com/turnstile/v0/api.js'))->toBe(1);
});

it('sends when Cloudflare vouches for the token, this form’s action and this site', function () {
	withTurnstile();
	cloudflareSays();

	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasNoErrors();

	Http::assertSent(fn (Request $request) => $request['secret'] === 'secret-key'
		&& $request['response'] === 'token'
		&& $request['remoteip'] === '127.0.0.1');
	Mail::assertQueued(ContactMessage::class);
});

it('refuses a missing token, a rejected one, and one Cloudflare could not check', function () {
	withTurnstile();
	$error = ['cf-turnstile-response' => 'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuche es nochmals.'];

	cloudflareSays(['success' => false]);
	$this->post('/de/kontakt', contactPayload())->assertSessionHasErrors($error);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'bad']))->assertSessionHasErrors($error);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => str_repeat('x', 2049)]))->assertSessionHasErrors($error);

	Http::fake(['challenges.cloudflare.com/*' => Http::response('', 500)]);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors($error);

	Http::fake(['challenges.cloudflare.com/*' => Http::failedConnection()]);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors($error);

	Mail::assertNothingQueued();
});

it('refuses a token made for another form or another site', function () {
	withTurnstile();
	$error = ['cf-turnstile-response' => 'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuche es nochmals.'];

	cloudflareSays(['action' => 'login']);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors($error);

	cloudflareSays(['hostname' => 'elsewhere.example']);
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors($error);

	Mail::assertNothingQueued();
});

it('refuses everything when no hostname is configured, without asking Cloudflare', function () {
	withTurnstile(['hostnames' => []]);
	Http::fake();

	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors('cf-turnstile-response');

	Http::assertNothingSent();
});

it('takes Cloudflare’s test secret at its word locally, and never in production', function () {
	withTurnstile(['secret_key' => '1x0000000000000000000000000000000AA', 'hostnames' => []]);
	Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'example.com'])]);

	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasNoErrors();

	// The rule itself: a production request would stop at CSRF first, since
	// the test suite's CSRF pass is for the testing environment only.
	app()['env'] = 'production';
	$check = Validator::make(['token' => 'fresh-token'], ['token' => [new Turnstile('contact')]]);

	expect($check->fails())->toBeTrue();
});

it('takes a token once, even when Cloudflare would take it again', function () {
	withTurnstile();
	cloudflareSays();

	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasNoErrors();
	$this->post('/de/kontakt', contactPayload(['cf-turnstile-response' => 'token']))->assertSessionHasErrors('cf-turnstile-response');

	Http::assertSentCount(1);
	Mail::assertQueuedCount(1);
});
