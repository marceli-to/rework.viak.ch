{{--
	The site search's result page ([[15-search]]), `/de/suche?q=…`: every hit
	as one list, in the order the index ranked them, each saying what it is
	with its badge (Marcel, 2026-10-26, rather than groups).

	**Full width since 2026-11-17** (Marcel): it sat in the article's copy
	column beside an aside saying *Suche*, eight columns for rows that carry a
	title, a lead and a line of dates and prices. Now the field runs across
	the page, and each hit is a row of the event list's grid
	(`card/event.blade.php`): black rule, 12 columns, 16/40 gap
	([[row.search-hit]]).

	Kept out of search engines: a page of results is not a page.
--}}
<x-layout.site title="Suche" heading="Suche" noindex>
	<div class="mb-48 lg:mb-64">
		<form method="get" action="{{ \App\Support\SiteUrl::search() }}" role="search">
			<x-form.field name="q" label="Suchbegriff" type="search" :value="$query" autocomplete="off" autofocus class="[&::-webkit-search-cancel-button]:appearance-none" />
		</form>

		@if ($query === '')
			<p class="text-md text-gray-600 sm:text-lg lg:text-xl">Zum Beispiel «Rhino», «Visualisieren», «KI» oder ein Name.</p>
		@elseif ($hits->isEmpty())
			<x-ui.no-results>Nichts gefunden für «{{ $query }}». Vielleicht ein anderes Wort, oder ein Blick in die <a href="{{ \App\Support\SiteUrl::courses() }}" class="underline decoration-1 underline-offset-[3px] hover:no-underline">Kurse</a>?</x-ui.no-results>
		@else
			<p class="text-md text-gray-600 sm:text-lg lg:text-xl">{{ $hits->count() === 1 ? '1 Eintrag' : $hits->count().' Einträge' }} gefunden für «{{ $query }}»</p>

			<div>
				@foreach ($hits as $hit)
					<x-row.search-hit :hit="$hit" />
				@endforeach
			</div>
		@endif
	</div>
</x-layout.site>
