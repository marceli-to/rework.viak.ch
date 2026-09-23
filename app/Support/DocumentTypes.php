<?php

declare(strict_types=1);

namespace App\Support;

/**
 * What the expert portal's two upload forms take — *Dokumente hochladen* and
 * the composer's *Anhänge* ([[09-public-site]]).
 *
 * Legacy's list (`shared/modules/files/Index.vue`), which it enforced in the
 * browser only, plus JPG, PNG and TIFF (Marcel, 2026-09-23; HEIC was in the
 * list for an hour and came out as unnecessary). Checked twice on the server:
 * the name's extension, which is what the `accept` attribute and the drop box
 * filter on, and the type sniffed from the content, so a renamed `.exe` fails
 * as well.
 *
 * **The content list is wider than the extension list on purpose.** libmagic
 * does not always name an old binary Office file precisely — depending on its
 * version a `.doc` or `.xls` can come back as `application/vnd.ms-office`,
 * `application/CDFV2` or `application/x-ole-storage`, and those map to no
 * extension at all, which is why this is `mimetypes` and not `mimes`. What the
 * wider list admits is still one of these formats; the extension rule keeps
 * the name honest.
 *
 * Probed 2026-09-23 against legacy's 2,399 stored documents (every one a PDF or
 * a zip, all sniffed correctly) and against a `.doc`, `.docx`, `.txt`, `.jpg`,
 * `.png` and `.tif` generated on macOS.
 */
final class DocumentTypes
{
	/** @var list<string> */
	public const EXTENSIONS = [
		'pdf', 'zip', 'txt',
		'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
		'jpg', 'jpeg', 'png', 'tif', 'tiff',
	];

	/** @var list<string> */
	public const MIME_TYPES = [
		'application/pdf',
		'application/zip',
		'application/x-zip-compressed',
		'text/plain',
		'application/msword',
		'application/vnd.ms-excel',
		'application/vnd.ms-powerpoint',
		'application/vnd.ms-office',
		'application/CDFV2',
		'application/x-ole-storage',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'image/jpeg',
		'image/png',
		'image/tiff',
	];

	/** The line under the drop box, in legacy's words and order. */
	public const RESTRICTIONS = 'pdf, zip, txt, doc, ppt, xls, jpg, png, tiff | max. 32 MB';

	/** @return list<string> */
	public static function rules(): array
	{
		return [
			'extensions:'.implode(',', self::EXTENSIONS),
			'mimetypes:'.implode(',', self::MIME_TYPES),
		];
	}

	/** For the input's `accept` attribute and the drop box's own check. */
	public static function accept(): string
	{
		return implode(',', array_map(fn (string $e) => '.'.$e, self::EXTENSIONS));
	}

	/** One sentence for both rules, since `mimetypes` would list twenty MIME types. */
	public static function message(): string
	{
		return 'Ungültiges Format. Erlaubt sind: '.strtoupper(str_replace(' | max. 32 MB', '', self::RESTRICTIONS)).'.';
	}
}
