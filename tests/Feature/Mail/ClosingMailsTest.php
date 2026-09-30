<?php

declare(strict_types=1);

use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Documents\RenderParticipationConfirmation;
use App\Actions\Events\SetEventState;
use App\Enums\EventState;
use App\Mail\EventClosedCustomer;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * *Event closed* ([[10-mail]]): the participation confirmation, with its PDF,
 * to seats ticked as attended and to nobody else (Marcel, 2026-09-29).
 */
beforeEach(function () {
	Storage::fake('documents');
	$this->admin = User::factory()->admin()->create();
	$this->event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]))
		->create(['date' => now()->addDays(3)->toDateString()]);
	$this->event->dates()->create(['date' => now()->addDays(3)->toDateString()]);

	$this->came = User::factory()->create();
	$this->missed = User::factory()->create();
	foreach ([$this->came, $this->missed] as $student) {
		app(CreateBookingForUser::class)->execute($this->event->refresh(), $student);
	}
	$this->event->update(['date' => now()->subDay()->toDateString()]);
});

it('closes with who attended, in one step', function () {
	$came = $this->came->bookings()->first();
	$missed = $this->missed->bookings()->first();
	// A tick from before is not kept when the lightbox leaves it off.
	$missed->forceFill(['participated_at' => now()])->save();
	Mail::fake();

	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/close", ['attended' => [$came->uuid]])
		->assertOk()
		->assertJsonPath('data.state', 'closed')
		->assertJsonPath('data.attended', 1);

	expect($came->refresh()->hasParticipated())->toBeTrue()
		->and($missed->refresh()->hasParticipated())->toBeFalse();
	Mail::assertQueued(EventClosedCustomer::class, 1);
	Mail::assertQueued(EventClosedCustomer::class, fn ($mail) => $mail->hasTo($this->came->email));
});

it('wants at least one attendee where there are seats', function () {
	Mail::fake();

	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/close", ['attended' => []])
		->assertJsonValidationErrors(['attended' => 'Bitte mindestens einen Teilnehmer auswählen.']);
	// A uuid from somewhere else is not an attendee either.
	$this->postJson("/api/admin/events/{$this->event->uuid}/close", ['attended' => [(string) Str::uuid()]])
		->assertJsonValidationErrors('attended');

	expect($this->event->refresh()->state)->not->toBe(EventState::Closed);
	Mail::assertNothingQueued();
});

it('closes a date nobody booked with an empty list', function () {
	$this->event->bookings()->update(['cancelled_at' => now()]);

	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/close", ['attended' => []])->assertOk();

	expect($this->event->refresh()->state)->toBe(EventState::Closed);
});

it('closes only a date that has run, only once, and only for admins', function () {
	$attended = ['attended' => [$this->came->bookings()->first()->uuid]];
	$this->actingAs($this->came)->postJson("/api/admin/events/{$this->event->uuid}/close", $attended)->assertForbidden();

	$this->event->update(['date' => now()->addDay()->toDateString()]);
	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/close", $attended)->assertStatus(422);

	$this->event->update(['date' => now()->subDay()->toDateString()]);
	$this->postJson("/api/admin/events/{$this->event->uuid}/close", $attended)->assertOk();
	$this->postJson("/api/admin/events/{$this->event->uuid}/close", $attended)->assertStatus(422);
});

it('does not close through the plain state switch, which knows nothing of attendance', function () {
	$this->actingAs($this->admin)->patchJson("/api/admin/events/{$this->event->uuid}/state", ['state' => 'closed'])
		->assertJsonValidationErrors('state');
});

it('confirms participation to ticked seats only, with the certificate', function () {
	$this->came->bookings()->first()->forceFill(['participated_at' => now()])->save();
	Mail::fake();

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);

	Mail::assertQueued(EventClosedCustomer::class, 1);
	Mail::assertQueued(EventClosedCustomer::class, fn ($mail) => $mail->hasTo($this->came->email)
		&& Storage::disk('documents')->exists($mail->certificate->path())
		&& count($mail->attachments()) === 1);
});

it('does not confirm twice', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);
	Mail::fake();

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);
	Mail::assertNothingQueued();
});

it('shows the page with each participant and whether they attended', function () {
	$this->came->bookings()->first()->forceFill(['participated_at' => now()])->save();

	$page = $this->actingAs($this->admin)->getJson("/api/admin/events/{$this->event->uuid}/page")->assertOk()->json('data');

	expect($page['course']['title'])->toBe('Rhino Einstiegskurs')
		->and(collect($page['participants'])->firstWhere('email', $this->came->email)['participated'])->toBeTrue()
		->and(collect($page['participants'])->firstWhere('email', $this->missed->email)['participated'])->toBeFalse();
});

it('writes legacy text', function () {
	$booking = $this->came->bookings()->first();
	$certificate = app(RenderParticipationConfirmation::class)->execute($booking);

	expect((new EventClosedCustomer($booking->refresh(), $certificate))->render())
		->toContain('Teilnahmebestätigung – Rhino Einstiegskurs')
		->toContain('Hiermit bestätigen wir Deine Teilnahme an unserem Kurs:');
});

it('confirms a seat missed at closing, afterwards and once', function () {
	$came = $this->came->bookings()->first();
	$missed = $this->missed->bookings()->first();
	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/close", ['attended' => [$came->uuid]])->assertOk();
	Mail::fake();

	$confirm = "/api/admin/events/{$this->event->uuid}/bookings/{$missed->uuid}/confirm";
	$this->postJson($confirm)->assertOk()->assertJsonPath('data.participated', true);

	expect($missed->refresh()->hasParticipated())->toBeTrue();
	Mail::assertQueued(EventClosedCustomer::class, 1);
	Mail::assertQueued(EventClosedCustomer::class, fn ($mail) => $mail->hasTo($this->missed->email));

	// Not twice, and not for the seat closing already confirmed.
	$this->postJson($confirm)->assertStatus(422);
	$this->postJson("/api/admin/events/{$this->event->uuid}/bookings/{$came->uuid}/confirm")->assertStatus(422);
	Mail::assertQueued(EventClosedCustomer::class, 1);
});

it('confirms a seat only on a closed date, only its own, and only for admins', function () {
	$missed = $this->missed->bookings()->first();
	Mail::fake();

	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/bookings/{$missed->uuid}/confirm")->assertStatus(422);

	$other = Event::factory()->create(['state' => EventState::Closed]);
	$this->postJson("/api/admin/events/{$other->uuid}/bookings/{$missed->uuid}/confirm")->assertNotFound();

	$this->event->update(['state' => EventState::Closed]);
	$this->actingAs($this->missed)->postJson("/api/admin/events/{$this->event->uuid}/bookings/{$missed->uuid}/confirm")->assertForbidden();

	expect($missed->refresh()->hasParticipated())->toBeFalse();
	Mail::assertNothingQueued();
});
