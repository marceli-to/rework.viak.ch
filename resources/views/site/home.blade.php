{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home">
	{{--
		**Built in part** ([[04-content]], the review's homepage markers): the
		Vorhaben tiles (1), the call band (2) and the next course dates (3). The
		rest of the mockup's stack follows; the intro is still the stub's.
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

	{{--
		The call band (the review's homepage marker 2, "Text statisch
		hinterlegen"): the mockup's copy, **fixed here and not an editable
		field**. The Kurse filter's teal box (`.card-teaser-training`,
		[[course.filter]]) at the page's width.

		Reworked 2026-10-07 (Marcel: it read flat), with no colour or size the
		site does not have:
		- **Two levels of copy.** The question is the headline, in the Vorhaben
		  tiles' regular weight and their 28px from lg; one step larger than the
		  tiles below lg (20, then 24), or on a phone it is no bigger than the
		  sentence under it. The rest is regular weight below it, not a second
		  bold block.
		- **The number is the action**, at the headline's size. *Rückruf
		  vereinbaren* is a button, the site's `outline` one, which is white
		  with teal type and so stands out against the teal. It is the only
		  button in the band; the tiles above are links.
		- **More room**: 24px in, 40 from lg, the grid's own gap, and the two
		  halves centred on each other from sm.
	--}}
	<aside class="mt-48 bg-teal p-16 text-white sm:grid sm:grid-cols-12 sm:items-center sm:gap-16 sm:p-24 lg:mt-64 lg:gap-40 lg:p-40">
		<div class="sm:col-span-8">
			<h2 class="text-2xl leading-[1.2] text-balance sm:text-3xl lg:text-4xl">Nicht sicher, was du brauchst?</h2>
			<p class="mt-8 max-w-[34em] text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-2xl">
				Ruf an und sprich mit jemandem, der die Tools täglich nutzt. Etwa zwanzig Minuten, kostenlos, unverbindlich.
			</p>
		</div>

		<div class="mt-24 flex flex-col items-start gap-16 sm:col-span-4 sm:mt-0 sm:items-end">
			<p class="text-2xl leading-[1.2] font-bold tabular-nums sm:text-3xl lg:text-4xl">
				<a href="tel:+41435014040" class="whitespace-nowrap hover:underline hover:decoration-2 hover:underline-offset-4">+41 43 501 40 40</a>
			</p>
			<x-ui.button variant="outline" href="{{ \App\Support\SiteUrl::contact() }}#nachricht">Rückruf vereinbaren</x-ui.button>
		</div>
	</aside>

	{{--
		*Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
		Veranstaltungen, nobody picks them ([[HomeController]]). An open
		collapsible, as the course page lists its dates, and each one the
		portal's row (`x-row.event`), which leads with the course: the title
		links to the course, the dates, where and with whom, the fee. No course
		number and no state badge, which are the dashboard's. *Anmelden* goes to
		the course page, where booking already knows about full courses, an
		existing seat and the laptop question.
	--}}
	@if ($events->isNotEmpty())
		<x-ui.collapsible title="Nächste Kurstermine" class="mt-48 lg:mt-64" last>
			@foreach ($events as $event)
				<x-row.event :event="$event" :numbered="false" :show-state="false">
					<x-slot:action>
						<x-ui.button href="{{ \App\Support\SiteUrl::course($event->course->getTranslation('slug', app()->getLocale())) }}">Anmelden</x-ui.button>
					</x-slot:action>
				</x-row.event>
			@endforeach

			<a href="{{ \App\Support\SiteUrl::courses() }}" class="mt-32 inline-flex items-center gap-8 text-teal hover:underline">
				Alle Kurse und Termine
				<x-icon.arrow-right />
			</a>
		</x-ui.collapsible>
	@endif
</x-layout.site>
