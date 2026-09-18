<?php

declare(strict_types=1);

use App\Models\Media;
use App\Support\ImageFormats;
use App\View\Components\Media\Image;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\ComponentAttributeBag;

function render(Media|Collection $media, array $options = []): string
{
	$component = new Image($media, ...$options);

	return (string) app('view')->make('components.media.image', array_merge(
		get_object_vars($component),
		['attributes' => new ComponentAttributeBag],
	))->render();
}

it('sets width and height so the page does not shift', function () {
	$media = Media::factory()->cropped(w: 1000, h: 500)->create();

	$html = render($media);

	// 1920 is the widest generated size; the height follows the crop's ratio.
	expect($html)->toContain('width="1920"')->toContain('height="960"');
});

it('offers the formats the server can actually produce', function () {
	$html = render(Media::factory()->create());

	foreach (array_filter(['avif', 'webp'], fn (string $f) => ImageFormats::supports($f)) as $format) {
		expect($html)->toContain('type="image/'.$format.'"');
	}

	// JPEG is the <img> fallback, never a <source>.
	expect($html)->toContain('.jpg')->not->toContain('type="image/jpeg"');
});

it('passes the crop to the renderer as w,h,x,y', function () {
	$media = Media::factory()->cropped(w: 800, h: 600, x: 100, y: 50)->create();

	expect(render($media))->toContain(urlencode('800,600,100,50'));
});

it('art-directs a separate mobile crop', function () {
	$media = Media::factory()->create(['file' => 'wide.jpg'])
		->newCollection([
			Media::factory()->create(['file' => 'wide.jpg']),
			Media::factory()->mobile()->create(['file' => 'tall.jpg']),
		]);

	$html = render($media);

	expect($html)->toContain('media="(max-width: 767px)')
		->toContain('media="(min-width: 768px)')
		->toContain('tall.jpg')
		->toContain('wide.jpg');
});

it('serves an animated gif as itself', function () {
	$media = Media::factory()->create(['file' => 'spin.gif', 'mime_type' => 'image/gif']);

	expect(render($media))->toContain('/storage/uploads/spin.gif');
});

it('404s a missing image rather than erroring', function () {
	$this->get('/img/uploads/missing.jpg')->assertNotFound();
});

/**
 * An open resizer lets anyone fill the cache disk by varying a number, so only
 * the widths `<x-media.image>` would itself have produced are honoured.
 */
it('ignores a width it would never have generated', function () {
	$source = Storage::disk('public')->path('uploads/clamp-test.png');
	@mkdir(dirname($source), 0775, true);
	imagepng(imagecreatetruecolor(2000, 1000), $source);

	$this->get('/img/uploads/clamp-test.png?w=4999')
		->assertOk()
		->assertHeader('Content-Type', 'image/png');

	// 4999 was dropped, so what came back is the full-size original rather than
	// a 4999-wide render.
	$rendered = imagecreatefromstring($this->get('/img/uploads/clamp-test.png?w=4999')->getContent());
	expect(imagesx($rendered))->toBe(2000);

	$narrowed = imagecreatefromstring($this->get('/img/uploads/clamp-test.png?w=640')->getContent());
	expect(imagesx($narrowed))->toBe(640);

	@unlink($source);
});
