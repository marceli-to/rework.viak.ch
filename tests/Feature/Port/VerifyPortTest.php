<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Media;

/** `port:verify` ([[VerifyPort]], `.rework/Deploy.md`). */
it('passes when every polymorphic row has its owner', function () {
	$event = Event::factory()->create();
	Media::factory()->create(['mediable_type' => Event::class, 'mediable_id' => $event->id]);

	$this->artisan('port:verify')->assertSuccessful();
});

it('fails on a row whose owner is gone, and on a type that is no model', function () {
	Media::factory()->create(['mediable_type' => Event::class, 'mediable_id' => 999999]);

	$this->artisan('port:verify')->assertFailed();

	Media::query()->delete();
	Media::factory()->create(['mediable_type' => 'App\\Models\\Hero', 'mediable_id' => 1]);

	$this->artisan('port:verify')->assertFailed();
});
