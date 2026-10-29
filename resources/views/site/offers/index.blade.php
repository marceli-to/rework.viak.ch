{{--
	*Alle Angebote* ([[14-angebote]], [[OfferController]]). The mockup's
	content (`Alle-Angebote.html`) in the site's look: the top drawn as a
	Vorhaben's (the title teal in the aside, the copy in the column, Kurse
	and Software linked in it), then one collapsible per category, its
	count beside the title as the portals have it, the tiles four across from
	`lg`. The mockup's greyed *in Vorbereitung* tiles are not built: a tile
	exists only for what is published.
--}}
<x-layout.site title="Alle Angebote" heading="Angebote">
	<article class="mb-48 sm:grid sm:grid-cols-12 sm:gap-16 lg:mb-64 lg:gap-40">
		<aside class="text-teal max-sm:hidden sm:col-span-4">
			<h1 class="font-bold">Alle Angebote</h1>
		</aside>

		<div class="sm:col-span-8">
			{{-- Kurse and Software are linked here rather than switched to
			     (Marcel, 2026-10-29); their own pages carry the switch. --}}
			<p class="[&_a]:underline [&_a]:underline-offset-2 [&_a:hover]:no-underline">Jedes Werkzeug hat bei uns eine eigene Seite: <a href="{{ \App\Support\SiteUrl::courses() }}">Kurse</a> und <a href="{{ \App\Support\SiteUrl::softwareIndex() }}">Software</a> auf einen Blick, wo es beides gibt, sonst das, was wir anbieten. Sortiert nach Bereichen, alles an einem Ort.</p>
		</div>
	</article>

	@foreach ($sections as $section)
		<x-ui.collapsible :title="$section['title']" :count="$section['tiles']->count()" :last="$loop->last">
			<div class="mt-16 grid grid-cols-12 gap-16 sm:mt-32 lg:gap-40">
				@foreach ($section['tiles'] as $tile)
					<x-card.offer :tile="$tile" class="col-span-6 sm:col-span-4 lg:col-span-3" />
				@endforeach
			</div>
		</x-ui.collapsible>
	@endforeach
</x-layout.site>
