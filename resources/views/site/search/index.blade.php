{{--
	The site search's result page ([[15-search]]), `/de/suche?q=…`: the
	article with *Suche* in the aside, the field at the top of the column and
	every hit under it as one list, ranked, each with its badge, as the
	header's panel draws them; the field's rule is the first row's
	(Marcel, 2026-10-26).

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
		@elseif ($hits->isEmpty())
			<x-ui.no-results>Nichts gefunden für «{{ $query }}». Vielleicht ein anderes Wort, oder ein Blick in die <a href="{{ \App\Support\SiteUrl::courses() }}" class="underline decoration-1 underline-offset-[3px] hover:no-underline">Kurse</a>?</x-ui.no-results>
		@else
			{{-- Pulled up onto the field's rule: the field keeps its margin elsewhere. --}}
			<div class="-mt-16 lg:-mt-32 [&>a:first-child]:border-t-0">
				@foreach ($hits as $hit)
					<x-row.search-hit :hit="$hit" />
				@endforeach
			</div>
		@endif
	</x-layout.article>
</x-layout.site>
