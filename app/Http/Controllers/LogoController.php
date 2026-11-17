<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Software;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * **An Angebot's logo, served through the app** ([[Software::logoUrl]],
 * 2026-11-17). Not `/storage/logos/…`: on staging the `public/storage`
 * link pointed at the jailed shell's path, so nginx answered every file
 * behind it with 403 (`Deploy.md`, step 5, now makes the link relative).
 * Through the app the logos do not depend on that link, as the pictures do
 * not (`/img/`). The file is sent as stored, never re-encoded, and kept a
 * year: its name changes with every upload.
 */
class LogoController extends Controller
{
	public function show(string $file): BinaryFileResponse
	{
		$path = Software::LOGOS.'/'.$file;
		abort_unless(Storage::disk('public')->exists($path), 404);

		return response()->file(Storage::disk('public')->path($path), [
			'Cache-Control' => 'public, max-age=31536000, immutable',
		]);
	}
}
