<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The course form's image section — `/api/admin/courses/{course}/media` and
 * `/api/admin/media/{media}` ([[07-dashboard]]). Each action saves at once, as
 * legacy's image module does.
 */
beforeEach(function () {
	Storage::fake('public');
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
	$this->course = Course::factory()->create();
});

function courseImage(Course $course, array $attributes = []): Media
{
	return Media::factory()->for($course, 'mediable')->create($attributes);
}

it('keeps the image section to admins', function () {
	$this->actingAs(User::factory()->expert()->create())
		->getJson("/api/admin/courses/{$this->course->uuid}/media")
		->assertForbidden();
});

it('uploads an image straight onto the course, and makes the first one its teaser', function () {
	$first = $this->actingAs($this->admin)
		->postJson("/api/admin/courses/{$this->course->uuid}/media", ['file' => UploadedFile::fake()->image('Kurs Bild.jpg', 1600, 900)])
		->assertCreated()
		->json('data');

	$second = $this->postJson("/api/admin/courses/{$this->course->uuid}/media", ['file' => UploadedFile::fake()->image('zwei.jpg', 1600, 900)])
		->json('data');

	expect($first['role'])->toBe('teaser')
		->and($second['role'])->toBe('visual')
		->and($first['name'])->toBe('Kurs Bild.jpg')
		->and($first['src'])->toStartWith('/storage/uploads/kurs-bild-');

	Storage::disk('public')->assertExists(str_replace('/storage/', '', $first['src']));
	expect($this->course->media()->count())->toBe(2);
});

/**
 * A JPEG as a phone writes a portrait photo: landscape pixels, and an EXIF
 * Orientation of 6 ("rotate 90° clockwise") saying which way is up. The APP1
 * segment is built by hand because Imagick will not write the flag itself.
 */
function sidewaysPhoto(int $width, int $height): UploadedFile
{
	$image = new Imagick;
	$image->newImage($width, $height, 'red');
	$image->setImageFormat('jpeg');
	$jpeg = $image->getImageBlob();

	$tiff = "MM\x00\x2A\x00\x00\x00\x08"      // big-endian header, IFD at 8
		."\x00\x01"                              // one entry
		."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00" // Orientation, SHORT, 6
		."\x00\x00\x00\x00";                     // no next IFD
	$app1 = "Exif\x00\x00".$tiff;
	$segment = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1;

	$path = tempnam(sys_get_temp_dir(), 'exif');
	file_put_contents($path, substr($jpeg, 0, 2).$segment.substr($jpeg, 2));

	return new UploadedFile($path, 'IMG_5949.jpeg', 'image/jpeg', null, true);
}

it('stands a phone photo upright, however large', function (int $width, int $height, int $expectedWidth, int $expectedHeight) {
	$data = $this->actingAs($this->admin)
		->postJson("/api/admin/courses/{$this->course->uuid}/media", ['file' => sidewaysPhoto($width, $height)])
		->assertCreated()
		->json('data');

	$stored = new Imagick(Storage::disk('public')->path(str_replace('/storage/', '', $data['src'])));

	expect([$data['width'], $data['height']])->toBe([$expectedWidth, $expectedHeight])
		->and([$stored->getImageWidth(), $stored->getImageHeight()])->toBe([$expectedWidth, $expectedHeight])
		->and($stored->getImageOrientation())->toBeIn([Imagick::ORIENTATION_UNDEFINED, Imagick::ORIENTATION_TOPLEFT]);
})->with([
	'small enough to keep' => [400, 300, 300, 400],
	'shrunk as well' => [4032, 3024, 2400, 3200],
]);

it('refuses what is not an image, and anything over 16 MB', function () {
	$this->actingAs($this->admin)
		->postJson("/api/admin/courses/{$this->course->uuid}/media", ['file' => UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf')])
		->assertJsonPath('errors.file.0', 'Nur JPG, PNG oder WebP.');

	$this->postJson("/api/admin/courses/{$this->course->uuid}/media", ['file' => UploadedFile::fake()->image('riesig.jpg')->size(17000)])
		->assertJsonPath('errors.file.0', 'Höchstens 16 MB.');
});

it('lists the course’s images in their order, and leaves its documents out', function () {
	courseImage($this->course, ['file' => 'b.jpg', 'sort_order' => 1]);
	courseImage($this->course, ['file' => 'a.jpg', 'sort_order' => 0]);
	courseImage($this->course, ['file' => 'plan.pdf', 'mime_type' => 'application/pdf']);

	$srcs = array_column($this->actingAs($this->admin)->getJson("/api/admin/courses/{$this->course->uuid}/media")->json('data'), 'src');

	expect($srcs)->toBe(['/storage/uploads/a.jpg', '/storage/uploads/b.jpg']);
});

/** One teaser and one Open Graph image per course, as the ported data has. */
it('moves the teaser and the Open Graph flag rather than doubling them', function () {
	$old = courseImage($this->course, ['is_teaser' => true]);
	$new = courseImage($this->course);

	$this->actingAs($this->admin)->patchJson("/api/admin/media/{$new->uuid}/role", ['role' => 'teaser'])->assertJsonPath('data.role', 'teaser');
	expect($old->fresh()->is_teaser)->toBeFalse();

	$this->patchJson("/api/admin/media/{$old->uuid}/role", ['role' => 'og'])->assertJsonPath('data.role', 'og');
	$this->patchJson("/api/admin/media/{$new->uuid}/role", ['role' => 'visual'])->assertJsonPath('data.role', 'visual');

	expect($this->course->media()->where('is_teaser', true)->count())->toBe(0)
		->and($this->course->media()->where('is_og', true)->count())->toBe(1);
});

it('crops in the file’s pixels, shows the crop on the card, and clears it', function () {
	$image = courseImage($this->course, ['width' => 2000, 'height' => 1500]);

	$card = $this->actingAs($this->admin)
		->patchJson("/api/admin/media/{$image->uuid}/crop", ['x' => 100, 'y' => 50, 'w' => 1600, 'h' => 900])
		->json('data');

	expect($card['crop'])->toBe(['x' => 100, 'y' => 50, 'w' => 1600, 'h' => 900])
		->and($card['preview'])->toContain('crop=1600%2C900%2C100%2C50')
		->and($card['preview'])->toContain('w=480')->toContain('h=270');

	$this->patchJson("/api/admin/media/{$image->uuid}/crop", ['x' => null, 'y' => null, 'w' => null, 'h' => null])
		->assertJsonPath('data.crop', null);
});

it('keeps an alt text and a caption', function () {
	$image = courseImage($this->course);

	$this->actingAs($this->admin)
		->putJson("/api/admin/media/{$image->uuid}", ['alt' => 'Ein Laptop mit SketchUp', 'caption' => 'Im Kursraum'])
		->assertJsonPath('data.alt', 'Ein Laptop mit SketchUp')
		->assertJsonPath('data.caption', 'Im Kursraum');
});

it('saves a new order, touching only this course’s images', function () {
	$a = courseImage($this->course, ['sort_order' => 0]);
	$b = courseImage($this->course, ['sort_order' => 1]);
	$elsewhere = courseImage(Course::factory()->create(), ['sort_order' => 5]);

	$this->actingAs($this->admin)
		->patchJson("/api/admin/courses/{$this->course->uuid}/media/order", ['media' => [$b->uuid, $elsewhere->uuid, $a->uuid]])
		->assertNoContent();

	expect([$b->fresh()->sort_order, $a->fresh()->sort_order, $elsewhere->fresh()->sort_order])->toBe([0, 2, 5]);
});

it('deletes an image with its file', function () {
	Storage::disk('public')->put('uploads/weg.jpg', 'x');
	$image = courseImage($this->course, ['file' => 'weg.jpg']);

	$this->actingAs($this->admin)->deleteJson("/api/admin/media/{$image->uuid}")->assertNoContent();

	expect(Media::find($image->id))->toBeNull();
	Storage::disk('public')->assertMissing('uploads/weg.jpg');
});
