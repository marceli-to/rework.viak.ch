<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ImageFormats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Server;
use League\Glide\ServerFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders an image at the size and format the page asked for ([[08-accounts]]).
 *
 * Ported from `forrerzimmermann.ch` with one addition: **the parameters are
 * clamped.** There, `/img/{path}` takes any `w` and `h` off the query string,
 * which is fine on a brochure site and is not fine here — an open resizer on a
 * public site with VIAK's traffic lets anyone fill the cache disk by varying a
 * number. Glide offers `setSignKey` for this; clamping to the width list the
 * component already uses achieves the same thing without a key to manage, and
 * keeps hand-written URLs working.
 *
 * Nothing is pre-generated. A variant is rendered once, on first request, and
 * cached for a year.
 */
class ImageController extends Controller
{
	private readonly Server $server;

	public function __construct()
	{
		$this->server = ServerFactory::create([
			'source' => storage_path('app/public'),
			'cache' => storage_path('app/glide-cache'),
			'driver' => 'imagick',
		]);
	}

	public function show(Request $request, string $path): Response
	{
		$params = $this->clamp($request->all());

		try {
			$cached = $this->server->makeImage($path, $params);
		} catch (FileNotFoundException) {
			// A missing image is a 404, not a 500. It is the ordinary outcome of
			// a stale link or a deleted upload, and letting it reach the error
			// handler fills the log with something nobody can act on.
			throw new NotFoundHttpException;
		}

		return response($this->server->getCache()->read($cached), 200)
			->header('Content-Type', ImageFormats::mimeFor(
				(string) ($params['fm'] ?? pathinfo($path, PATHINFO_EXTENSION))
			))
			// Immutable in practice: a re-crop writes a new `crop` and therefore
			// a new URL, so a cached variant is never stale.
			->header('Cache-Control', 'public, max-age=31536000, immutable');
	}

	/**
	 * Only parameters we would have generated ourselves.
	 *
	 * @param  array<string, mixed>  $params
	 * @return array<string, mixed>
	 */
	private function clamp(array $params): array
	{
		$clamped = [];

		if (isset($params['w']) && in_array((int) $params['w'], ImageFormats::WIDTHS, true)) {
			$clamped['w'] = (int) $params['w'];
		}

		// Height is free-form but bounded, because it follows from the width and
		// the crop's aspect ratio rather than from a fixed list.
		if (isset($params['h'])) {
			$clamped['h'] = max(1, min((int) $params['h'], 4000));
		}

		if (isset($params['fit']) && in_array($params['fit'], ['crop', 'contain', 'max', 'fill'], true)) {
			$clamped['fit'] = $params['fit'];
		}

		if (isset($params['fm']) && ImageFormats::supports((string) $params['fm'])) {
			$clamped['fm'] = mb_strtolower((string) $params['fm']);
		}

		if (isset($params['q'])) {
			$clamped['q'] = max(1, min((int) $params['q'], 100));
		}

		// `w,h,x,y` in source pixels — the shape legacy already stored and the
		// shape Glide takes.
		if (isset($params['crop']) && preg_match('/^\d+,\d+,\d+,\d+$/', (string) $params['crop'])) {
			$clamped['crop'] = $params['crop'];
		}

		return $clamped;
	}
}
