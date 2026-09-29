<?php

declare(strict_types=1);

use App\Actions\Bookings\CreateBookingForUser;
use App\Actions\Documents\RenderParticipationConfirmation;
use App\Actions\Events\SetEventState;
use App\Enums\EventState;
use App\Mail\EventClosedStudent;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

	$this->came = User::factory()->student()->create();
	$this->missed = User::factory()->student()->create();
	foreach ([$this->came, $this->missed] as $student) {
		app(CreateBookingForUser::class)->execute($this->event->refresh(), $student);
	}
	$this->event->update(['date' => now()->subDay()->toDateString()]);
});

it('lets an admin tick a seat as attended, and untick it', function () {
	$booking = $this->came->bookings()->first();

	$this->actingAs($this->admin)->patchJson("/api/admin/bookings/{$booking->uuid}/participation", ['participated' => true])
		->assertOk()->assertJsonPath('data.participated', true);
	expect($booking->refresh()->hasParticipated())->toBeTrue();

	$this->patchJson("/api/admin/bookings/{$booking->uuid}/participation", ['participated' => false]);
	expect($booking->refresh()->hasParticipated())->toBeFalse();
});

it('keeps ticking to admins', function () {
	$this->actingAs($this->came)->patchJson('/api/admin/bookings/'.$this->came->bookings()->first()->uuid.'/participation', ['participated' => true])->assertForbidden();
});

it('confirms participation to ticked seats only, with the certificate', function () {
	$this->came->bookings()->first()->forceFill(['participated_at' => now()])->save();
	Mail::fake();

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);

	Mail::assertQueued(EventClosedStudent::class, 1);
	Mail::assertQueued(EventClosedStudent::class, fn ($mail) => $mail->hasTo($this->came->email)
		&& Storage::disk('documents')->exists($mail->certificate->path())
		&& count($mail->attachments()) === 1);
});

it('refuses a tick once the date is closed, and does not confirm twice', function () {
	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);
	Mail::fake();

	$this->actingAs($this->admin)->patchJson('/api/admin/bookings/'.$this->missed->bookings()->first()->uuid.'/participation', ['participated' => true])->assertStatus(422);

	app(SetEventState::class)->execute($this->event->refresh(), EventState::Closed);
	Mail::assertNothingQueued();
});

it('shows the page with each participant and their tick', function () {
	$this->came->bookings()->first()->forceFill(['participated_at' => now()])->save();

	$page = $this->actingAs($this->admin)->getJson("/api/admin/events/{$this->event->uuid}/page")->assertOk()->json('data');

	expect($page['course']['title'])->toBe('Rhino Einstiegskurs')
		->and(collect($page['participants'])->firstWhere('email', $this->came->email)['participated'])->toBeTrue()
		->and(collect($page['participants'])->firstWhere('email', $this->missed->email)['participated'])->toBeFalse();
});

it('writes legacy text', function () {
	$booking = $this->came->bookings()->first();
	$certificate = app(RenderParticipationConfirmation::class)->execute($booking);

	expect((new EventClosedStudent($booking->refresh(), $certificate))->render())
		->toContain('Teilnahmebestätigung – Rhino Einstiegskurs')
		->toContain('Hiermit bestätigen wir Deine Teilnahme an unserem Kurs:');
});
