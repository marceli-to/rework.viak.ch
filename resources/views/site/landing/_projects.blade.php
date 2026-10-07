{{--
	*Was möchtest du machen?* (the review's homepage marker 1): the
	published Vorhaben, in the order the dashboard dragged them into, each a
	tile to its page. Drawn with the course card's frame and heading
	(`components/cards/_teaser.scss`): a 1px teal border, the title teal at
	the card's sizes, the tile's line below it as the card's category label
	is drawn above. On hover it fills teal, as the card's overlay does.

	**One section with the call band** (Marcel, 2026-10-07): from lg the tiles
	take eight columns, two to a row, and the band stands beside them in the
	other four as a teal column ([[landing._call]]). Below lg the tiles stay
	three across and the band follows under them as before.
--}}
<section class="mt-48 lg:mt-64">
	<h2 class="mb-16 font-bold lg:mb-24">Was möchtest du machen?</h2>

	<div class="lg:grid lg:grid-cols-12 lg:gap-40">
		<div class="grid grid-cols-12 gap-16 lg:col-span-8 lg:gap-40">
			@foreach ($projects as $project)
				<a
					href="{{ \App\Support\SiteUrl::project($project->getTranslation('slug', app()->getLocale())) }}"
					class="group col-span-6 block border border-teal p-8 text-black transition-colors duration-[120ms] ease-in-out hover:bg-teal hover:text-white sm:col-span-4 lg:col-span-6 lg:p-16"
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

		@include('site.landing._call', ['beside' => true])
	</div>
</section>
