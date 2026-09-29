<?php

declare(strict_types=1);

use App\Enums\EventState;
use App\Events\BookingMade;
use App\Events\MessagePosted;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Storage;

/**
 * The course date's page on the dashboard ([[07-dashboard]], step 7):
 * *Teilnehmer hinzufügen*, the participant list, *Nachrichten* and
 * *Kurs-Dokumente*. Attendance is in `ClosingMailsTest`.
 */
beforeEach(function () {
	Storage::fake('public');
	Events::fake([BookingMade::class, MessagePosted::class]);
	$this->admin = User::factory()->admin()->create();
	$this->event = Event::factory()->for(Course::factory()->create(['title' => ['de' => 'Rhino Einstiegskurs']]))
		->create(['date' => now()->addDays(20)->toDateString()]);
	$this->event->dates()->create(['date' => now()->addDays(20)->toDateString()]);
	$this->student = User::factory()->student()->create();
});

it('books a student onto the date, once', function () {
	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/bookings", ['student' => $this->student->uuid])
		->assertCreated();

	expect($this->event->bookings()->active()->where('user_id', $this->student->id)->exists())->toBeTrue();
	Events::assertDispatched(BookingMade::class);

	$this->postJson("/api/admin/events/{$this->event->uuid}/bookings", ['student' => $this->student->uuid])
		->assertStatus(422);
});

it('books no deactivated account, and nothing on a date that is over', function () {
	$this->student->forceFill(['deactivated_at' => now()])->save();
	$this->actingAs($this->admin)->postJson("/api/admin/events/{$this->event->uuid}/bookings", ['student' => $this->student->uuid])
		->assertStatus(422);

	$other = User::factory()->student()->create();
	$this->event->forceFill(['state' => EventState::Cancelled])->save();
	$this->postJson("/api/admin/events/{$this->event->uuid}/bookings", ['student' => $other->uuid])
		->assertStatus(422);

	expect(Booking::query()->count())->toBe(0);
});

it('hands out the participant list as a PDF', function () {
	$this->actingAs($this->admin)->get("/api/admin/events/{$this->event->uuid}/participants")
		->assertOk()
		->assertHeader('Content-Type', 'application/pdf');
});

it('posts a message with its files, cleaned of anything but the editor\'s markup', function () {
	Booking::factory()->for($this->event)->for($this->student)->create();

	$this->actingAs($this->admin)->post("/api/admin/events/{$this->event->uuid}/messages", [
		'subject' => 'Anreise',
		'body' => '<p>Bitte <strong>pünktlich</strong>.</p><script>alert(1)</script>',
		'attachments' => [UploadedFile::fake()->create('handout.pdf', 40, 'application/pdf')],
	], ['Accept' => 'application/json'])->assertCreated();

	$message = Message::query()->where('event_id', $this->event->id)->sole();

	expect($message->body)->not->toContain('script')
		->and($message->body)->toContain('<strong>pünktlich</strong>')
		->and($message->media)->toHaveCount(1);

	$this->getJson("/api/admin/events/{$this->event->uuid}/page")
		->assertJsonPath('data.messages.0.subject', 'Anreise')
		->assertJsonPath('data.messages.0.attachments.0.name', 'handout.pdf');
});

it('uploads course documents and removes them, but never a message\'s', function () {
	$reply = $this->actingAs($this->admin)->post("/api/admin/events/{$this->event->uuid}/files", [
		'files' => [UploadedFile::fake()->create('workshop.pdf', 500, 'application/pdf')],
	], ['Accept' => 'application/json'])->assertCreated();

	$uuid = $reply->json('data.0.uuid');
	$this->getJson("/api/admin/events/{$this->event->uuid}/page")->assertJsonPath('data.files.0.name', 'workshop.pdf');

	$this->deleteJson("/api/admin/events/{$this->event->uuid}/files/{$uuid}")->assertNoContent();
	expect($this->event->media()->count())->toBe(0);

	$this->post("/api/admin/events/{$this->event->uuid}/messages", [
		'subject' => 'Datei',
		'body' => '<p>Anbei.</p>',
		'attachments' => [UploadedFile::fake()->create('anhang.pdf', 40, 'application/pdf')],
	], ['Accept' => 'application/json'])->assertCreated();
	$attachment = Message::query()->sole()->media->first();

	$this->deleteJson("/api/admin/events/{$this->event->uuid}/files/{$attachment->uuid}")->assertNotFound();
});

it('uploads course documents with their Bezeichnung', function () {
	$this->actingAs($this->admin)->post("/api/admin/events/{$this->event->uuid}/files", [
		'files' => [
			UploadedFile::fake()->create('workshop.pdf', 50, 'application/pdf'),
			UploadedFile::fake()->create('modelle.pdf', 50, 'application/pdf'),
		],
		'captions' => ['', 'Modelle Tag 1'],
	], ['Accept' => 'application/json'])->assertCreated();

	$this->getJson("/api/admin/events/{$this->event->uuid}/page")
		->assertJsonPath('data.files.0.caption', null)
		->assertJsonPath('data.files.1.caption', 'Modelle Tag 1')
		->assertJsonPath('data.files.1.name', 'modelle.pdf');
});

it('keeps all of it to admins', function () {
	$this->actingAs($this->student)->postJson("/api/admin/events/{$this->event->uuid}/bookings", ['student' => $this->student->uuid])->assertForbidden();
	$this->get("/api/admin/events/{$this->event->uuid}/participants")->assertForbidden();
	$this->postJson("/api/admin/events/{$this->event->uuid}/messages", ['subject' => 'x', 'body' => '<p>x</p>'])->assertForbidden();
});
