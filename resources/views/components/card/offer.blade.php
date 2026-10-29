@props(['tile'])

{{--
	One tile on *Alle Angebote* ([[14-angebote]]): the mockup's small tile
	(`Alle-Angebote.html`) in the course card's frame, a 1px teal border, the
	title teal, and under it what the Angebot holds (*Kurs & Software*,
	*Software*, *Kurs*) with the arrow the site's links on carry. No picture:
	the client asked for small tiles, and a course without an Angebot has the
	same tile as one with.
--}}
<a href="{{ $tile['href'] }}" {{ $attributes->class(['group flex flex-col justify-between gap-16 border border-teal p-8 text-black lg:p-16']) }}>
	<h3 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-xl lg:text-2xl">{{ $tile['title'] }}</h3>

	<div class="flex items-center justify-between gap-8 text-md text-gray-600 transition-colors group-hover:text-teal lg:text-lg">
		{{ $tile['kind'] }}
		<x-icon.arrow-right class="shrink-0" />
	</div>
</a>
