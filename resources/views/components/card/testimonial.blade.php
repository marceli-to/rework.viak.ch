@props(['testimonial'])

@php
	$locale = app()->getLocale();
	$context = $testimonial->getTranslation('context', $locale, false);
@endphp

{{--
	One quote, on Firmenschulung and a course page's *Kundenmeinungen*
	(Marcel, 2026-10-06): the expert and team
	cards' frame ([[card.team]]), a 1px teal border with 8px inside, 16 from
	lg, so the quotes read as the site's cards rather than as Kontakt's rows.
	The quote leads; who said it stands under it, the name bold and black,
	not teal as the other cards' names are (Marcel, 2026-10-06).
--}}
<article {{ $attributes->class(['flex flex-col justify-between border border-teal p-8 lg:p-16']) }}>
	<blockquote class="text-md leading-[1.4] sm:text-lg lg:text-xl">„{{ $testimonial->getTranslation('quote', $locale) }}“</blockquote>

	<footer class="mt-16 text-md sm:text-lg lg:text-xl">
		<p class="font-bold">{{ $testimonial->name }}</p>
		@if ($context)
			<p>{{ $context }}</p>
		@endif
	</footer>
</article>
