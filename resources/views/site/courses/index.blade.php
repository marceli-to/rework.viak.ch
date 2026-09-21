<x-layout.site title="Kurse">
	{{-- The filter trigger sits beside the page title on a phone, as it does on
	     the live site. It lives outside the filter's own Alpine scope, so it
	     asks for the panel through an event rather than reaching into it. --}}
	<x-slot:actions>
		<button type="button" x-data @click="$dispatch('open-filter')" class="sm:hidden" aria-label="Filter anzeigen">
			<x-icon.filter />
		</button>
	</x-slot:actions>

	{{--
		Rebuilt 1:1 from legacy's `frontend/filter/Index.vue`: an outer
		12-column grid, the cards in `span-8` as their own 12-column grid of
		`span-6` pairs, and the filter in `span-4`.

		Legacy rendered all of it through a Vue island that POSTed to
		`/api/course/filter` on every click, with the card markup living a second
		time in `filter/components/Card.vue`. Here the page is server-rendered:
		the filter is links carrying a query string, so it works with no
		JavaScript and is indexable — and Alpine then hides and shows the cards
		already on the page, so changing a filter is not a navigation
		([[09-public-site]]).

		`courseFilter` holds one value per attribute, seeded from what the server
		filtered by so the two agree on the first paint. Only Software is wired;
		the attribute is named in every place that handles it, so the rest of
		legacy's seven arrive as data rather than as code.
	--}}
	<div
		class="grid grid-cols-12 gap-x-16 gap-y-16 lg:gap-x-40"
		x-data="courseFilter({ software: @js($activeSoftware) })"
		@open-filter.window="open = true"
	>
		<div class="col-span-12 sm:col-span-8">
			{{-- Covers both an empty catalogue and a filter that matches
			     nothing; the server picks the first state, Alpine the rest. --}}
			<div @class(['hidden' => count($matching) > 0]) :class="{ hidden: count > 0 }">
				Leider keine Kurse gefunden.
			</div>

			<div class="grid grid-cols-12 gap-x-16 gap-y-16 lg:gap-x-40">
				@foreach ($courses as $course)
					{{-- `data-facets` is the card's own copy of what it can be
					     filtered by, so the browser needs no second payload and
					     no uuid is written down twice. `::class` escapes to a
					     literal `:class` — on a component tag Blade would read
					     one colon as a PHP expression. --}}
					<x-site.course-card
						:course="$course"
						:eager="$loop->index < 2"
						data-facets="{{ json_encode(['software' => $course->software->pluck('uuid')->all()]) }}"
						::class="{ hidden: !matches($el) }"
						@class([
							'col-span-12 sm:col-span-6',
							'hidden' => ! in_array($course->uuid, $matching, true),
						])
					/>
				@endforeach
			</div>
		</div>

		<div class="col-span-12 sm:col-span-4">
			<x-site.course-filter :software="$software" :active="$activeSoftware" :matching="count($matching)" />
		</div>
	</div>
</x-layout.site>
