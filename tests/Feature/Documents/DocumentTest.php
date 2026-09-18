<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
	Storage::fake('documents');
	$this->student = User::factory()->student()->create();
});

it('lets a customer download their own invoice', function () {
	$document = UserDocument::factory()->for($this->student)->onDisk()->create();

	$this->actingAs($this->student)
		->get("/dokumente/{$document->uuid}")
		->assertOk()
		->assertDownload($document->filename);
});

/**
 * The reason documents moved off the public disk. Legacy served all 1,162 from
 * under the `public/storage` symlink, protected by nothing but the uuid in the
 * path being unguessable.
 */
it('will not hand one customer another customer’s invoice', function () {
	$document = UserDocument::factory()->onDisk()->create();

	$this->actingAs($this->student)
		->get("/dokumente/{$document->uuid}")
		->assertForbidden();
});

it('will not hand a document to a guest at all', function () {
	$document = UserDocument::factory()->onDisk()->create();

	$this->get("/dokumente/{$document->uuid}")->assertRedirect();
});

/** Teaching a course does not entitle anyone to its students' invoices. */
it('will not hand a student’s invoice to an expert', function () {
	$document = UserDocument::factory()->onDisk()->create();

	$this->actingAs(User::factory()->expert()->create())
		->get("/dokumente/{$document->uuid}")
		->assertForbidden();
});

it('lets an admin fetch any document', function () {
	$document = UserDocument::factory()->onDisk()->create();

	$this->actingAs(User::factory()->admin()->create())
		->get("/dokumente/{$document->uuid}")
		->assertOk();
});

it('404s when the row is there but the file is not', function () {
	$document = UserDocument::factory()->for($this->student)->create();

	$this->actingAs($this->student)
		->get("/dokumente/{$document->uuid}")
		->assertNotFound();
});

/**
 * The path is derived, never stored. Legacy kept a `uri` column holding a public
 * path and got the separator wrong on 271 rows, leaving 95 students with
 * download links that 404 — a computed path cannot acquire a typo.
 */
it('derives the path from the owner and the filename', function () {
	$document = UserDocument::factory()->for($this->student)->create(['filename' => 'x.pdf']);

	expect($document->path())->toBe("documents/{$this->student->uuid}/x.pdf");
});

/**
 * 17 legacy paths carry more than one row, up to 37 for a single booking,
 * because a Mailable generated the PDF and inserted the row from its own
 * constructor. The constraint legacy did not have.
 */
it('refuses a second row for the same file', function () {
	UserDocument::factory()->for($this->student)->create(['filename' => 'same.pdf']);
	UserDocument::factory()->for($this->student)->create(['filename' => 'same.pdf']);
})->throws(UniqueConstraintViolationException::class);
