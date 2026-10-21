<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Event;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/** `media:move-attachments` ([[MoveAttachments]], `.rework/Deploy.md`). */
beforeEach(function () {
	Storage::fake('public');
	Storage::fake('attachments');
});

it('moves a Veranstaltung s file off the public disk, leaves images, and can run again', function () {
	$event = Event::factory()->create();
	$document = Media::factory()->create(['mediable_type' => Event::class, 'mediable_id' => $event->id]);
	$image = Media::factory()->create(['mediable_type' => Course::class, 'mediable_id' => $event->course_id]);
	Storage::disk('public')->put('uploads/'.$document->file, 'pdf');
	Storage::disk('public')->put('uploads/'.$image->file, 'jpg');

	$this->artisan('media:move-attachments')->expectsOutput('1 moved.')->assertSuccessful();

	Storage::disk('attachments')->assertExists($document->file);
	Storage::disk('public')->assertMissing('uploads/'.$document->file);
	Storage::disk('public')->assertExists('uploads/'.$image->file);

	$this->artisan('media:move-attachments')->expectsOutput('0 moved.')->assertSuccessful();
	expect(Storage::disk('attachments')->get($document->file))->toBe('pdf');
});
