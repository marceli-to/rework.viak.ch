@props(['images', 'alt' => ''])

{{--
	The homepage intro's slider: legacy's Swiper (`vendor/_swiper.scss`, its
	setup in `frontend/home.js`), driven by Alpine ([[slider]]).

	16:9 at the column's width, each slide the image in its crop. The arrows
	are Swiper's own chevrons recoloured teal, as legacy sets them: 32px high
	(`--swiper-navigation-size: 32px`), about 20 wide, 10px in from each side,
	centred on the image. One image is drawn without the track or the arrows.

	The first image loads eagerly, being the largest thing above the fold;
	the rest lazily, which still fetches the next one, a slide's width away.
--}}
@if ($images->count() === 1)
	<x-media.image :media="$images->first()" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" :alt="$alt" loading="eager" {{ $attributes->class(['block w-full']) }} />
@elseif ($images->isNotEmpty())
	<div
		{{ $attributes->class(['relative overflow-hidden']) }}
		x-data="slider({{ $images->count() }})"
		@touchstart.passive="touchStart($event)"
		@touchend="touchEnd($event)"
		role="region"
		aria-roledescription="carousel"
		aria-label="Bilder"
	>
		<div
			class="flex"
			:class="animate && 'transition-transform duration-300 ease-out'"
			:style="`transform: translateX(-${index * 100}%)`"
			@transitionend.self="settle()"
		>
			@foreach ($images as $image)
				<div class="w-full shrink-0" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} von {{ $images->count() }}">
					<x-media.image :media="$image" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" :alt="$alt" :loading="$loop->first ? 'eager' : 'lazy'" class="block w-full" />
				</div>
			@endforeach

			{{-- The first again, so the loop has somewhere to land ([[slider]]). --}}
			<div class="w-full shrink-0" aria-hidden="true">
				<x-media.image :media="$images->first()" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" alt="" loading="lazy" class="block w-full" />
			</div>
		</div>

		<button type="button" class="absolute top-1/2 left-10 z-10 -mt-16 flex h-32 w-20 items-center justify-center text-teal" title="Vorheriges Bild" @click="prev()">
			<svg class="h-32 w-20" viewBox="0 0 20 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2 4 16l14 14" /></svg>
		</button>
		<button type="button" class="absolute top-1/2 right-10 z-10 -mt-16 flex h-32 w-20 items-center justify-center text-teal" title="Nächstes Bild" @click="next()">
			<svg class="h-32 w-20" viewBox="0 0 20 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m2 2 14 14L2 30" /></svg>
		</button>
	</div>
@endif
