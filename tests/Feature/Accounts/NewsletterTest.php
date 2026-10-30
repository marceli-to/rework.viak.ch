<?php

declare(strict_types=1);

use App\Actions\Accounts\CreateAccount;
use App\Models\Country;
use App\Models\User;
use App\Support\Newsletter\FakeNewsletterList;
use App\Support\Newsletter\Mailchimp;
use App\Support\Newsletter\NewsletterList;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Legacy's Mailchimp sync, kept (client, 2026-10-30, #13): the footer's
 * signup and the *Newsletter abonnieren* box ([[NewsletterList]],
 * [[SyncNewsletter]]). Production only, like the books.
 */
beforeEach(function () {
	Country::query()->firstOrCreate(['code' => 'ch'], ['name' => ['de' => 'Schweiz'], 'order' => 1]);
	RateLimiter::clear('newsletter:127.0.0.1');
});

function newsletterList(): FakeNewsletterList
{
	return app(NewsletterList::class);
}

/** Fresh from the database, as a request finds it: every column, the box included. */
function newsletterReader(array $attributes = []): User
{
	return User::factory()->create([
		'email_verified_at' => now(),
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'zip' => '7752',
		'city' => 'Orsières',
		'country_code' => 'ch',
		...$attributes,
	])->refresh();
}

function portalSave(array $overrides = []): array
{
	return [
		'first_name' => 'Antonia',
		'last_name' => 'Haller',
		'street' => 'Kaiserstr.',
		'zip' => '7752',
		'city' => 'Orsières',
		'country_code' => 'ch',
		'subscribe_newsletter' => '0',
		...$overrides,
	];
}

it('keeps the real list unreachable outside production, key or no key', function () {
	config()->set('services.mailchimp', ['key' => 'abc-us10', 'list_id' => 'c19300bbec', 'tag' => 'VA Newsletter']);
	app()->forgetInstance(NewsletterList::class);

	expect(app(NewsletterList::class))->toBeInstanceOf(FakeNewsletterList::class);
});

it('subscribes the footer’s signup', function () {
	$this->post('/de/newsletter', ['firstname' => 'Anna', 'name' => 'Muster', 'email' => 'anna@example.test'])
		->assertRedirect('/de#newsletter')
		->assertSessionHas('newsletter', 'sent');

	expect(newsletterList()->subscribed)->toBe(['anna@example.test']);
});

it('subscribes a new account with the box ticked, and leaves one without alone', function () {
	$create = app(CreateAccount::class);

	$create->execute(['first_name' => 'A', 'last_name' => 'B', 'email' => 'yes@example.test', 'subscribe_newsletter' => true], []);
	$create->execute(['first_name' => 'C', 'last_name' => 'D', 'email' => 'no@example.test', 'subscribe_newsletter' => false], []);

	expect(newsletterList()->subscribed)->toBe(['yes@example.test'])
		->and(newsletterList()->unsubscribed)->toBe([]);
});

it('saves the portal’s box, which it did not before, and follows it', function () {
	$user = newsletterReader();

	$this->actingAs($user)->post('/de/konto/bearbeiten', portalSave(['subscribe_newsletter' => '1']))->assertRedirect('/de/konto');
	expect($user->refresh()->subscribe_newsletter)->toBeTrue()
		->and(newsletterList()->subscribed)->toBe([$user->email]);

	$this->actingAs($user)->post('/de/konto/bearbeiten', portalSave())->assertRedirect('/de/konto');
	expect($user->refresh()->subscribe_newsletter)->toBeFalse()
		->and(newsletterList()->unsubscribed)->toBe([$user->email]);
});

/**
 * Legacy synced on every save, and so unsubscribed whoever had the box
 * unticked whenever their street number was corrected.
 */
it('sends nothing for a save the list does not care about', function () {
	$user = newsletterReader();

	$this->actingAs($user)->post('/de/konto/bearbeiten', portalSave(['street' => 'Neugasse']))->assertRedirect('/de/konto');

	expect(newsletterList()->subscribed)->toBe([])
		->and(newsletterList()->unsubscribed)->toBe([]);
});

it('updates a subscriber whose name changes', function () {
	$user = newsletterReader(['subscribe_newsletter' => true]);

	$this->actingAs($user)->post('/de/konto/bearbeiten', portalSave(['subscribe_newsletter' => '1', 'last_name' => 'Meier']));

	expect(newsletterList()->subscribed)->toBe([$user->email]);
});

it('leaves the box as it is when a client does not send it', function () {
	$user = newsletterReader(['subscribe_newsletter' => true]);

	$this->actingAs($user)->putJson('/api/profile', ['first_name' => 'Antonia', 'last_name' => 'Haller'])->assertOk();

	expect($user->refresh()->subscribe_newsletter)->toBeTrue()
		->and(newsletterList()->unsubscribed)->toBe([]);
});

it('saves the profile even when the list fails', function () {
	app()->instance(NewsletterList::class, new class implements NewsletterList
	{
		public function subscribe(string $email, string $firstName, string $lastName): void
		{
			throw new RuntimeException('Mailchimp is down');
		}

		public function unsubscribe(string $email): void {}
	});

	$user = newsletterReader();

	$this->actingAs($user)->post('/de/konto/bearbeiten', portalSave(['subscribe_newsletter' => '1']))->assertRedirect('/de/konto');

	expect($user->refresh()->subscribe_newsletter)->toBeTrue();
});

it('sends Mailchimp what legacy sent: the member, then its two tags', function () {
	Http::fake();

	(new Mailchimp('abc-us10', 'c19300bbec', 'VA Newsletter'))->subscribe('Anna@Example.test', 'Anna', 'Muster');

	$member = 'https://us10.api.mailchimp.com/3.0/lists/c19300bbec/members/'.md5('anna@example.test');

	Http::assertSentInOrder([
		fn (Request $request) => $request->method() === 'PUT' && $request->url() === $member
			&& $request['status_if_new'] === 'subscribed'
			&& $request['merge_fields'] === ['FNAME' => 'Anna', 'LNAME' => 'Muster'],
		fn (Request $request) => $request->method() === 'POST' && $request->url() === $member.'/tags'
			&& $request['tags'] === [['name' => 'VA Newsletter', 'status' => 'active'], ['name' => 'Deutsch', 'status' => 'active']],
	]);
});

it('takes an address the list never held off it without complaint', function () {
	Http::fake(['*' => Http::response(['status' => 404], 404)]);

	(new Mailchimp('abc-us10', 'c19300bbec', 'VA Newsletter'))->unsubscribe('never@example.test');

	Http::assertSentCount(1);
});

it('refuses to build the real client without a key', function () {
	new Mailchimp('', '', '');
})->throws(RuntimeException::class, 'not configured');
