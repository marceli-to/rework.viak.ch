<?php

declare(strict_types=1);

use App\Actions\Messages\PostMessage;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;

beforeEach(function () {
	$this->event = Event::factory()->create();
	$this->expert = User::factory()->expert()->create();
	$this->expert->eventsAsExpert()->attach($this->event);
	$this->student = User::factory()->student()->create();
	Booking::factory()->for($this->event)->for($this->student)->create();
});

it('records who a message went to, at the moment it was sent', function () {
	$other = User::factory()->student()->create();
	Booking::factory()->for($this->event)->for($other)->create();

	$message = app(PostMessage::class)->execute($this->event, $this->expert, 'Raum', 'B12');

	expect($message->recipients)->toHaveCount(2);

	// A later cancellation must not rewrite who was told what.
	$this->student->bookings()->first()->forceFill(['cancelled_at' => now()])->save();

	expect($message->refresh()->recipients)->toHaveCount(2);
});

it('leaves out seats that had already been given up', function () {
	Booking::factory()->for($this->event)->cancelled()->create();

	$message = app(PostMessage::class)->execute($this->event, $this->expert, 'Raum', 'B12');

	expect($message->recipients)->toHaveCount(1);
});

it('copies the author in when asked', function () {
	$message = app(PostMessage::class)
		->execute($this->event, $this->expert, 'Raum', 'B12', copyToAuthor: true);

	expect($message->recipients->pluck('id'))->toContain($this->expert->id);
});

it('lets the expert teaching the course post to it', function () {
	$this->actingAs($this->expert)
		->postJson("/api/events/{$this->event->uuid}/messages", [
			'subject' => 'Raum geändert',
			'body' => 'Wir sind in B12.',
		])
		->assertCreated()
		->assertJsonPath('data.recipient_count', 1);
});

/**
 * Legacy gated this by `role:admin,expert,student` and its FormRequest
 * authorised everything, so any authenticated student could post to any event —
 * and the post mails every participant.
 */
it('will not let a student post to a course', function () {
	$this->actingAs($this->student)
		->postJson("/api/events/{$this->event->uuid}/messages", [
			'subject' => 'Hallo',
			'body' => 'An alle.',
		])
		->assertForbidden();
});

it('will not let an expert post to a course they do not teach', function () {
	$other = User::factory()->expert()->create();

	$this->actingAs($other)
		->postJson("/api/events/{$this->event->uuid}/messages", [
			'subject' => 'Hallo',
			'body' => 'An alle.',
		])
		->assertForbidden();
});

it('lets a booked student read the thread', function () {
	Message::factory()->for($this->event)->create();

	$this->actingAs($this->student)
		->getJson("/api/events/{$this->event->uuid}/messages")
		->assertOk()
		->assertJsonCount(1, 'data');
});

/** The read half of the same gap: any student could read any course's thread. */
it('will not show the thread to a student who never booked', function () {
	Message::factory()->for($this->event)->create();

	$this->actingAs(User::factory()->student()->create())
		->getJson("/api/events/{$this->event->uuid}/messages")
		->assertForbidden();
});

it('closes the thread once a student cancels', function () {
	Message::factory()->for($this->event)->create();
	$this->student->bookings()->first()->forceFill(['cancelled_at' => now()])->save();

	$this->actingAs($this->student)
		->getJson("/api/events/{$this->event->uuid}/messages")
		->assertForbidden();
});

it('lets an author withdraw their own message and nobody else’s', function () {
	$mine = Message::factory()->for($this->event)->for($this->expert, 'author')->create();
	$theirs = Message::factory()->for($this->event)->create();

	$this->actingAs($this->expert)->deleteJson("/api/messages/{$mine->uuid}")->assertNoContent();
	$this->actingAs($this->expert)->deleteJson("/api/messages/{$theirs->uuid}")->assertForbidden();
});
