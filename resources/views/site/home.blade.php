{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home">
	{{--
		**Not built yet, beyond the Vorhaben.** The live homepage is a stack of
		sections, and `04-content.md` says to assemble it **last**, from the
		partials the other pages need, so each is designed against more than a
		single caller. The intro is still the stub's.
	--}}
	<section>
		<h1 class="font-bold text-teal">Visualisierungs-Akademie</h1>

		<p class="mt-16 max-w-[40em]">
			Kurse für digitales Gestalten: Modellieren, Visualisieren, Animieren,
			Editieren. Unterrichtet von Leuten, die damit täglich arbeiten.
		</p>

		<a href="{{ \App\Support\SiteUrl::courses() }}" class="mt-24 inline-flex items-center gap-8 text-teal hover:underline">
			Kurse ansehen
			<x-icon.arrow-right />
		</a>
	</section>

	{{--
		*Was möchtest du machen?* (the review's homepage marker 1): the
		published Vorhaben, in the order the dashboard dragged them into, each a
		tile to its page. Drawn with the course card's frame and heading
		(`components/cards/_teaser.scss`): a 1px teal border, the title teal at
		the card's sizes, the tile's line below it as the card's category label
		is drawn above. On hover it fills teal, as the card's overlay does.
	--}}
	@if ($projects->isNotEmpty())
		<section class="mt-48 lg:mt-64">
			<h2 class="mb-16 font-bold lg:mb-24">Was möchtest du machen?</h2>

			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($projects as $project)
					<a
						href="{{ \App\Support\SiteUrl::project($project->getTranslation('slug', app()->getLocale())) }}"
						class="group col-span-6 block border border-teal p-8 text-black transition-colors duration-[120ms] ease-in-out hover:bg-teal hover:text-white sm:col-span-4 lg:p-16"
					>
						<h3 class="text-lg leading-[1.2] break-words hyphens-auto text-teal group-hover:text-white sm:text-2xl lg:text-4xl">
							{{ $project->getTranslation('title', app()->getLocale()) }}
						</h3>

						@if ($teaser = $project->getTranslation('teaser', app()->getLocale(), false))
							<p class="mt-8 text-xxs leading-[1.3] font-medium text-gray-600 group-hover:text-white sm:text-sm lg:text-lg">{{ $teaser }}</p>
						@endif
					</a>
				@endforeach
			</div>
		</section>
	@endif
</x-layout.site>
