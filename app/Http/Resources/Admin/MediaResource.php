<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One image in the dashboard's image section ([[07-dashboard]]).
 *
 * `src` is the file itself, which the cropper draws and measures — the crop is
 * in its pixels. `preview` is what a card shows: the image through Glide at the
 * card's size, **in its crop**, as legacy's cards show it.
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		$params = ['w' => 480, 'fit' => 'crop', 'fm' => 'jpg', 'q' => 80];

		if ($this->crop) {
			$c = $this->crop;
			$params['h'] = (int) round(480 * $c['h'] / max(1, $c['w']));
			$params['crop'] = "{$c['w']},{$c['h']},{$c['x']},{$c['y']}";
		} else {
			$params['h'] = (int) round(480 * ($this->height ?: 1) / max(1, $this->width ?: 1));
		}

		return [
			'uuid' => $this->uuid,
			'src' => '/storage/uploads/'.$this->file,
			'preview' => '/img/uploads/'.$this->file.'?'.http_build_query($params),
			'name' => $this->original_name ?? $this->file,
			'width' => $this->width,
			'height' => $this->height,
			'crop' => $this->crop,
			'role' => $this->is_teaser ? 'teaser' : ($this->is_og ? 'og' : 'visual'),
			'alt' => $this->alt ?? '',
			'caption' => $this->caption ?? '',
		];
	}
}
