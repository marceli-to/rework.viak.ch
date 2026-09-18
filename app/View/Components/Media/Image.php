<?php

declare(strict_types=1);

namespace App\View\Components\Media;

use App\Models\Media;
use App\Support\ImageFormats;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * A responsive `<picture>` ([[08-accounts]]).
 *
 * Ported from `forrerzimmermann.ch`. What it does that legacy's `/img/{template}/…`
 * could not: negotiate AVIF → WebP → JPEG against what the server can actually
 * produce, generate a srcset across eight widths, carry explicit `width` and
 * `height` so the page does not shift as images load, and art-direct a separate
 * mobile crop.
 *
 * The aspect ratio comes from the **crop** where there is one, so the height is
 * known before the file is fetched.
 *
 * @param  Media|Collection<int, Media>  $media  one image, or the desktop/mobile pair
 */
class Image extends Component
{
	public string $alt;

	public int $width;

	public int $height;

	public float $aspectRatio;

	public string $fallbackUrl;

	/** @var array<int, array<string, string>> */
	public array $sources = [];

	private readonly Media $desktop;

	private readonly ?Media $mobile;

	/** @var array<int, string> */
	private readonly array $formats;

	/** @var array<int, int> */
	private readonly array $widths;

	public function __construct(
		Media|Collection $media,
		public string $sizes = '100vw',
		int $maxWidth = 1920,
		?string $alt = null,
		public string $fit = 'crop',
		public int $quality = 82,
		public string $class = '',
		public string $loading = 'lazy',
	) {
		[$this->desktop, $this->mobile] = $this->variantsOf($media);

		$this->alt = $alt ?? $this->desktop->alt ?? '';
		$this->formats = array_values(array_filter(
			['avif', 'webp', 'jpg'],
			fn (string $format) => ImageFormats::supports($format),
		));

		$widths = array_values(array_filter(ImageFormats::WIDTHS, fn (int $w) => $w <= $maxWidth));
		$this->widths = $widths === [] ? [ImageFormats::WIDTHS[0]] : $widths;

		$this->aspectRatio = $this->fit === 'crop'
			? $this->desktop->aspectRatio()
			: ($this->desktop->height ?: 1) / ($this->desktop->width ?: 1);

		// `end()` takes its argument by reference, and `$widths` is readonly —
		// so read the last element without moving an internal pointer.
		$this->width = $this->widths[array_key_last($this->widths)];
		$this->height = (int) round($this->width * $this->aspectRatio);

		// An animated GIF survives nothing Glide would do to it, so it is served
		// as itself.
		if ($this->desktop->mime_type === 'image/gif') {
			$this->fallbackUrl = '/storage/uploads/'.$this->desktop->file;

			return;
		}

		$this->buildSources();
	}

	/**
	 * Splits the argument into a desktop image and an optional mobile one.
	 *
	 * Not called `resolve()`: `Illuminate\View\Component::resolve()` is static,
	 * and overriding it with an instance method is a fatal error at class-load
	 * time — which surfaces as the test runner dying with no output at all.
	 *
	 * @return array{0: Media, 1: ?Media}
	 */
	private function variantsOf(Media|Collection $media): array
	{
		if ($media instanceof Media) {
			return [$media, null];
		}

		$desktop = $media->firstWhere('variant', 'desktop');
		$mobile = $media->firstWhere('variant', 'mobile');

		// One variant on its own is the primary one, whichever it is.
		$desktop ??= $mobile ?? $media->first();

		if (! $desktop instanceof Media) {
			throw new InvalidArgumentException('An image component needs at least one media item.');
		}

		return [$desktop, $desktop === $mobile ? null : $mobile];
	}

	private function buildSources(): void
	{
		if ($this->mobile instanceof Media) {
			$narrow = array_values(array_filter($this->widths, fn (int $w) => $w <= 768));

			foreach ($this->formats as $format) {
				if ($format === 'jpg') {
					continue;
				}

				$this->sources[] = [
					'srcset' => $this->srcset($this->mobile, $format, $narrow ?: [480]),
					'type' => ImageFormats::mimeFor($format),
					'sizes' => $this->sizes,
					'media' => '(max-width: 767px)',
				];
			}
		}

		foreach ($this->formats as $format) {
			if ($format === 'jpg') {
				continue;
			}

			$source = [
				'srcset' => $this->srcset($this->desktop, $format, $this->widths),
				'type' => ImageFormats::mimeFor($format),
				'sizes' => $this->sizes,
			];

			if ($this->mobile instanceof Media) {
				$source['media'] = '(min-width: 768px)';
			}

			$this->sources[] = $source;
		}

		$this->fallbackUrl = $this->url($this->desktop, 'jpg', $this->width);
	}

	/** @param  array<int, int>  $widths */
	private function srcset(Media $media, string $format, array $widths): string
	{
		return implode(', ', array_map(
			fn (int $w) => $this->url($media, $format, $w).' '.$w.'w',
			$widths,
		));
	}

	private function url(Media $media, string $format, int $width): string
	{
		$ratio = $this->fit === 'crop'
			? $media->aspectRatio()
			: ($media->height ?: 1) / ($media->width ?: 1);

		$params = [
			'w' => $width,
			'h' => (int) round($width * $ratio),
			'fit' => $this->fit,
		];

		if ($this->fit === 'crop' && $media->crop) {
			$c = $media->crop;
			$params['crop'] = "{$c['w']},{$c['h']},{$c['x']},{$c['y']}";
		}

		$params['fm'] = $format;
		$params['q'] = $this->quality;

		return '/img/uploads/'.$media->file.'?'.http_build_query($params);
	}

	public function render(): View
	{
		return view('components.media.image');
	}
}
