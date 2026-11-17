{{--
	**News** beside *Nächste Kurstermine* (Oliver in Roxy, 2026-11-12, built
	2026-11-17, [[News]]): "ein kleines Bild, ein kleiner Text und einen Pfeil
	mit Link". Under a heading on the course dates' line,
	one per row on a black rule (below), the title bold, the text, and the
	site's arrow when it links somewhere ([[NewsLink]]). The whole item is
	the link. No picture, no grey box: the text stands alone. Edited in
	*Seiteninhalte → News*.
--}}
<section>
	{{-- The heading as the course dates' is, and **one item per row on their
	     black rule** (Marcel, 2026-11-17: a divider each, not two side by
	     side), so the two columns read as one list each. The picture, when
	     there is one, small on the left; the title, the text and the arrow
	     beside it. --}}
	<h2 class="mb-16 font-bold lg:mb-24">Aktuell</h2>

	<ul role="list">
		@foreach ($news as $item)
			@php
				$url = $item->url();
				$image = $item->teaser();
			@endphp
			<li class="border-t border-black py-8 sm:py-12">
				<{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" @endif class="group flex items-start gap-16 lg:gap-24">
					@if ($image)
						<x-media.image :media="$image" ratio="3/2" sizes="(min-width: 1132px) 160px, 120px" :max-width="480" class="block w-120 shrink-0 lg:w-160" />
					@endif
					<span class="block min-w-0">
						<span @class(['block font-bold leading-[1.4] sm:text-lg lg:text-xl', 'group-hover:text-teal' => $url])>{{ $item->getTranslation('title', app()->getLocale()) }}</span>
						@if ($text = $item->getTranslation('text', app()->getLocale(), false))
							<span class="mt-4 block text-md leading-[1.4] text-gray-600 sm:text-lg">{{ $text }}</span>
						@endif
						@if ($url)
							<x-icon.arrow-right class="mt-8 transition-colors group-hover:text-teal" />
						@endif
					</span>
				</{{ $url ? 'a' : 'div' }}>
			</li>
		@endforeach
	</ul>
</section>
