@props(['images', 'alt' => ''])

{{--
	The homepage intro's slider: legacy's Swiper (`vendor/_swiper.scss`, its
	setup in `frontend/home.js`), started by Alpine ([[slider]]).

	16:9 at the column's width, each slide the image in its crop. The arrows
	are Swiper's chevrons recoloured teal, as legacy sets them: 32px high
	(`--swiper-navigation-size: 32px`), about 20 wide, 10px in from each side,
	centred on the image; drawn here as SVG, so Swiper's icon font is not
	loaded. One image is drawn without Swiper or the arrows.

	The first image loads eagerly, being the largest thing above the fold;
	the rest lazily.
--}}
@if ($images->count() === 1)
	<x-media.image :media="$images->first()" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" :alt="$alt" loading="eager" {{ $attributes->class(['block w-full']) }} />
@elseif ($images->isNotEmpty())
	<div {{ $attributes->class(['relative']) }} x-data="slider">
		<div class="swiper aspect-video w-full" x-ref="track">
			<div class="swiper-wrapper">
				@foreach ($images as $image)
					<div class="swiper-slide">
						<x-media.image :media="$image" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" :alt="$alt" :loading="$loop->first ? 'eager' : 'lazy'" class="block w-full" />
					</div>
				@endforeach
			</div>
		</div>

		<button type="button" x-ref="prev" class="absolute top-1/2 left-10 z-10 -mt-16 flex h-32 w-20 cursor-pointer items-center justify-center text-teal" title="Vorheriges Bild">
			<svg class="h-32 w-20" viewBox="0 0 20 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2 4 16l14 14" /></svg>
		</button>
		<button type="button" x-ref="next" class="absolute top-1/2 right-10 z-10 -mt-16 flex h-32 w-20 cursor-pointer items-center justify-center text-teal" title="Nächstes Bild">
			<svg class="h-32 w-20" viewBox="0 0 20 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m2 2 14 14L2 30" /></svg>
		</button>
	</div>
@endif
