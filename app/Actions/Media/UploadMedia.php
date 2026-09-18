<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Takes a file and makes a media row out of it ([[08-accounts]]).
 *
 * Lands in `temp/` rather than `uploads/`, because the crop UI works on an
 * upload before it is attached to anything and an abandoned upload should not
 * litter the live directory. [[AttachMedia]] moves it across.
 */
class UploadMedia
{
	public function __construct(private readonly NormalizeImage $normalize) {}

	public function execute(UploadedFile $file): Media
	{
		$filename = $this->uniqueName($file->getClientOriginalName());
		$mimeType = $file->getMimeType();

		$file->storeAs('temp', $filename, 'public');
		$path = Storage::disk('public')->path('temp/'.$filename);

		// Before the dimensions are read, so what is stored describes the file
		// as it now is. There is no crop yet, so nothing has to be rescaled.
		$this->normalize->execute($path, $mimeType);

		[$width, $height] = @getimagesize($path) ?: [null, null];

		return new Media([
			'uuid' => (string) Str::uuid(),
			'file' => $filename,
			'original_name' => $file->getClientOriginalName(),
			'mime_type' => $mimeType,
			'size' => @filesize($path) ?: 0,
			'width' => $width,
			'height' => $height,
			'variant' => 'desktop',
		]);
	}

	/**
	 * Slugged, so the filename is safe in a URL, plus six random characters so
	 * two people uploading `portrait.jpg` do not overwrite each other.
	 */
	private function uniqueName(string $original): string
	{
		$name = Str::slug(pathinfo($original, PATHINFO_FILENAME));
		$extension = Str::lower(pathinfo($original, PATHINFO_EXTENSION));

		return $name.'-'.Str::random(6).'.'.$extension;
	}
}
