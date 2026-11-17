{{--
	**News** beside *Nächste Kurstermine* (Oliver in Roxy, 2026-11-12, built
	2026-11-17, [[News]]): "ein kleines Bild, ein kleiner Text und einen Pfeil
	mit Link". Under a heading on the course dates' line, two side by side
	from `sm`, each its picture (3:2), the title bold, the text, and the
	site's small arrow when it links somewhere ([[NewsLink]]). The whole item
	is the link. No picture, no grey box: the text stands alone. Edited in
	*Seiteninhalte → News*.
--}}
<section>
	{{-- The heading as the course dates' is, and the black rule their first row
	     has, so the two columns start on one line. --}}
	<h2 class="mb-16 font-bold lg:mb-24">Aktuell</h2>

	<div class="grid grid-cols-1 gap-32 border-t border-black pt-16 sm:grid-cols-2 sm:gap-16 sm:pt-24 lg:gap-40">
		@foreach ($news as $item)
			@php
				$url = $item->url();
				$image = $item->teaser();
			@endphp
			<{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" @endif class="group flex flex-col">
				@if ($image)
					<x-media.image :media="$image" ratio="3/2" sizes="(min-width: 1132px) 270px, (min-width: 700px) 45vw, 100vw" :max-width="800" class="mb-12 block w-full" />
				@endif
				<h3 @class(['font-bold leading-[1.3] sm:text-lg lg:text-xl', 'group-hover:text-teal' => $url])>{{ $item->getTranslation('title', app()->getLocale()) }}</h3>
				@if ($text = $item->getTranslation('text', app()->getLocale(), false))
					<p class="mt-4 text-md leading-[1.4] text-gray-600 sm:text-lg">{{ $text }}</p>
				@endif
				@if ($url)
					<x-icon.arrow-right class="mt-8 transition-colors group-hover:text-teal" />
				@endif
			</{{ $url ? 'a' : 'div' }}>
		@endforeach
	</div>
</section>
