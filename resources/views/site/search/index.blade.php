{{--
	The site search's result page ([[15-search]]), `/de/suche?q=…`. Proposed
	2026-10-26, in the site's own pieces: the article with *Suche* in the
	aside, the query in a form field at the top of the column, the hits
	grouped in open collapsibles with their counts, each a [[row.search-hit]].

	Kept out of search engines: a page of results is not a page.
--}}
<x-layout.site title="Suche" heading="Suche" noindex>
	<x-layout.article class="mb-48 lg:mb-64">
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Suche</h1>
		</x-slot:aside>

		<form method="get" action="{{ \App\Support\SiteUrl::search() }}" role="search">
			<x-form.field name="q" label="Kurs oder Software" type="search" :value="$query" autocomplete="off" autofocus class="[&::-webkit-search-cancel-button]:appearance-none" />
		</form>

		@if ($query === '')
			<p class="text-md text-gray-600 sm:text-lg lg:text-xl">Zum Beispiel «Rhino», «Visualisieren» oder «KI».</p>
		@elseif ($count === 0)
			<x-ui.no-results>Nichts gefunden für «{{ $query }}». Vielleicht ein anderes Wort, oder ein Blick in die <a href="{{ \App\Support\SiteUrl::courses() }}" class="underline decoration-1 underline-offset-[3px] hover:no-underline">Kurse</a>?</x-ui.no-results>
		@endif
	</x-layout.article>

	@foreach ($groups as $group)
		<x-ui.collapsible :title="$group['label']" :count="$group['hits']->count()" :last="$loop->last">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-8 sm:col-start-5">
					@foreach ($group['hits'] as $hit)
						<x-row.search-hit :hit="$hit" :badge="false" />
					@endforeach
				</div>
			</div>
		</x-ui.collapsible>
	@endforeach
</x-layout.site>
