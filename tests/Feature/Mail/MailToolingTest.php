<?php

declare(strict_types=1);

use App\Console\Commands\PlayScenario;
use App\Mail\BookingCompleted;
use App\Mail\VIAKMail;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Course;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The tools for watching the mail flows ([[10-mail]], layers 3 and 5): the
 * scenarios, the header that says who a caught mail was for, `/dev/mails`.
 */
beforeEach(function () {
	Storage::fake('documents');
	Country::factory()->swiss()->create();
	config(['mail.admin' => 'office@example.test']);
});

it('names the intended recipient on a caught mail', function () {
	$booking = Booking::factory()->create();

	Mail::to('anna@example.test')->sendNow(new BookingCompleted($booking));

	$message = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
	expect($message->getHeaders()->get(VIAKMail::INTENDED_TO)->getBodyAsString())->toBe('anna@example.test')
		->and($message->getTo()[0]->getAddress())->not->toBe('anna@example.test');
});

it('plays every scenario through, prints its mails, and leaves nothing behind', function (string $name) {
	$before = [User::withTrashed()->count(), Course::withTrashed()->count(), Event::withTrashed()->count(), Booking::withTrashed()->count(), Invoice::withTrashed()->count(), UserDocument::count()];

	$this->artisan('scenario:play', ['name' => $name, '--no-pause' => true])
		->expectsOutputToContain('✉')
		->expectsOutputToContain('Removed.')
		->assertSuccessful();

	expect([User::withTrashed()->count(), Course::withTrashed()->count(), Event::withTrashed()->count(), Booking::withTrashed()->count(), Invoice::withTrashed()->count(), UserDocument::count()])->toBe($before)
		->and(Storage::disk('documents')->allFiles())->toBe([]);
})->with(array_keys(PlayScenario::SCENARIOS));

it('sends the confirm scenario\'s mails to the people they are for', function () {
	$this->artisan('scenario:play', ['name' => 'confirm', '--no-pause' => true])
		->expectsOutputToContain('Min. Teilnehmerzahl erreicht')
		->expectsOutputToContain('+ viak-rechnung-')
		->expectsOutputToContain('+ viak-teilnahmebestaetigung-')
		->assertSuccessful();

	$certificates = collect(app('mailer')->getSymfonyTransport()->messages())
		->map->getOriginalMessage()
		->filter(fn ($message) => str_starts_with($message->getSubject(), 'Teilnahmebestätigung'));

	// Anna was ticked as attended, Beat was not.
	expect($certificates)->toHaveCount(1)
		->and($certificates->first()->getHeaders()->get(VIAKMail::INTENDED_TO)->getBodyAsString())->toContain('-anna@viak.test');
});

it('keeps the scenario\'s data when asked', function () {
	$this->artisan('scenario:play', ['name' => 'rental', '--no-pause' => true, '--keep' => true])
		->expectsOutputToContain('Kept.')
		->assertSuccessful();

	expect(User::query()->where('email', 'like', 'szenario-%')->count())->toBe(3);
});

it('lists the scenarios, and refuses one it does not know', function () {
	$this->artisan('scenario:play')->expectsOutputToContain('late-booker')->assertSuccessful();
	$this->artisan('scenario:play', ['name' => 'paid'])->assertFailed();
});

it('reminds only the dates it is told to', function () {
	$event = fn () => tap(Event::factory()->create(['date' => now()->addDays(5)->toDateString()]), fn ($e) => $e->dates()->create(['date' => $e->date]));
	[$mine, $other] = [$event(), $event()];
	Mail::fake();

	$this->artisan('events:remind', ['--event' => [$mine->uuid]])->assertSuccessful();

	expect($mine->refresh()->reminded_at)->not->toBeNull()
		->and($other->refresh()->reminded_at)->toBeNull();
});

it('previews every mail from fixtures, and leaves nothing behind', function () {
	$users = User::withTrashed()->count();
	Mail::fake();

	$index = $this->get('/dev/mails')->assertOk()->assertSee('Kursbestätigung – Rhino Einstiegskurs');

	preg_match_all('#/dev/mails/([a-z-]+)#', $index->getContent(), $keys);
	expect(array_unique($keys[1]))->toHaveCount(27);

	foreach (array_unique($keys[1]) as $key) {
		$this->get("/dev/mails/{$key}")->assertOk()->assertSee('Visualisierungs-Akademie');
	}

	$this->get('/dev/mails/absage-gutschrift')->assertSee('GUT890');
	$this->get('/dev/mails/unknown')->assertNotFound();
	expect(User::withTrashed()->count())->toBe($users);
	Mail::assertNothingOutgoing();
});
