<?php

declare(strict_types=1);

use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

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
