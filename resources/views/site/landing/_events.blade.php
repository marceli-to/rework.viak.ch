{{--
	*Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
	Veranstaltungen, nobody picks them ([[HomeController]]). Under a heading
	with the link to the Kurse page beside it, as *Beliebte Angebote* is (an
	open collapsible until 2026-10-07, Marcel). Until 2026-11-17 each was the
	portal's row (`x-row.event`) with dates, place, expert and fee; now one
	compact line (below). *Anmelden* goes to the course page, where booking
	already knows about full courses, an existing seat and the laptop
	question.
--}}
<section>
	{{-- The heading alone, as *Aktuell* beside it is; the way to the Kurse
	     page under the list (Marcel, 2026-11-17). --}}
	<h2 class="mb-16 font-bold lg:mb-24">Nächste Kurstermine</h2>

	{{--
		**Compact** (Oliver in Roxy, 2026-11-12, built 2026-11-17: "viel zu
		gross"): one line per Veranstaltung on the site's black rule, its
		start date short (*12.11.*, no year, no time, no second day), the
		course's title cut to one line with an ellipsis (whole in its
		`title`), *Anmelden* on the right. Where, with whom and the fee are
		gone; the course page has them. On a phone the title wraps and the
		button keeps its line.
	--}}
	<ul role="list">
		@foreach ($events as $event)
			@php($href = \App\Support\SiteUrl::course($event->course->getTranslation('slug', app()->getLocale())))
			<li class="flex items-center gap-16 border-t border-black py-8 leading-[1.4] sm:py-12 sm:text-lg lg:gap-24 lg:text-xl">
				<span class="w-56 shrink-0 font-bold tabular-nums lg:w-72">{{ $event->date->format('d.m.') }}</span>
				<a href="{{ $href }}" title="{{ $event->course->getTranslation('title', app()->getLocale()) }}" class="min-w-0 flex-1 hover:text-teal max-sm:line-clamp-2 sm:truncate">{{ $event->course->getTranslation('title', app()->getLocale()) }}</a>
				<x-ui.button :href="$href" class="shrink-0 max-sm:w-auto! max-sm:px-12">Anmelden</x-ui.button>
			</li>
		@endforeach
	</ul>

	{{-- Under the list, closing it on the site's black rule: *Alle Kurse und
	     Termine* (Marcel, 2026-11-17: was *Mehr* beside the heading), the
	     rows' size, teal, with the arrow. --}}
	<div class="border-t border-black pt-8 sm:pt-12">
		<a href="{{ \App\Support\SiteUrl::courses() }}" class="inline-flex items-center gap-8 text-teal hover:underline sm:text-lg lg:text-xl">
			Alle Kurse und Termine
			<x-icon.arrow-right />
		</a>
	</div>
</section>
