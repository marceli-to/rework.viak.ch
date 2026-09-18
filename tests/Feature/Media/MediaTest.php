<?php

declare(strict_types=1);

use App\Actions\Media\CropMedia;
use App\Models\Media;

it('derives orientation from the file rather than a stored column', function () {
	$landscape = Media::factory()->create(['width' => 2000, 'height' => 1500]);
	$portrait = Media::factory()->create(['width' => 1500, 'height' => 2000]);
	$square = Media::factory()->create(['width' => 1000, 'height' => 1000]);

	expect($landscape->orientation())->toBe('landscape')
		->and($portrait->orientation())->toBe('portrait')
		->and($square->orientation())->toBe('square');
});

it('takes the aspect ratio from the crop when there is one', function () {
	$media = Media::factory()->cropped(w: 1000, h: 500)->create(['width' => 2000, 'height' => 1500]);

	expect($media->aspectRatio())->toBe(0.5);
});

it('falls back to the file’s own ratio with no crop', function () {
	$media = Media::factory()->create(['width' => 2000, 'height' => 1000]);

	expect($media->aspectRatio())->toBe(0.5);
});

/**
 * Legacy said "no crop" with `0,0,0,0` and special-cased the literal string to
 * avoid rendering a 1×1 image. Null says it once.
 */
it('clears the crop rather than storing zeroes', function () {
	$media = Media::factory()->cropped()->create();

	app(CropMedia::class)->execute($media, ['x' => 0, 'y' => 0, 'w' => 0, 'h' => 0]);

	expect($media->refresh()->crop)->toBeNull();
});

it('stores a crop as four integers in source pixels', function () {
	$media = Media::factory()->create();

	app(CropMedia::class)->execute($media, ['x' => 10, 'y' => 20, 'w' => 300, 'h' => 400]);

	expect($media->refresh()->crop)->toMatchArray(['x' => 10, 'y' => 20, 'w' => 300, 'h' => 400]);
});
