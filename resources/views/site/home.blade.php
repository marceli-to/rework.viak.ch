{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home">
	{{--
		**Not built.** The live homepage is a stack of sections — hero, the
		"Was möchtest du machen?" tiles, next dates, the Firmenschulung teaser,
		experts, testimonials, Aktuelles, newsletter — and almost all of it is
		content that chunk 04 owns ([[04-content]]). `04-content.md` also says to
		build it **last**, after the partials it is assembled from exist, so that
		each one is designed against more than a single caller.

		So this is a stub rather than an invented design. It links to the one
		page that is finished.
	--}}
	<section>
		<h1 class="text-3xl leading-[1.2] text-teal">Visualisierungs-Akademie</h1>

		<p class="mt-16 max-w-[40em]">
			Kurse für digitales Gestalten — Modellieren, Visualisieren, Animieren,
			Editieren. Unterrichtet von Leuten, die damit täglich arbeiten.
		</p>

		<a href="{{ \App\Support\SiteUrl::courses() }}" class="mt-24 inline-flex items-center gap-8 text-teal hover:underline">
			Kurse ansehen
			<x-icon.arrow-right />
		</a>
	</section>
</x-layout.site>
