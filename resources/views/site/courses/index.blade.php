<x-layout.site title="Kurse">
	@php
		$facets = $filter->facets();
		$matching = $filter->matching();
	@endphp

	{{--
		Rebuilt 1:1 from legacy's `frontend/filter/Index.vue`: an outer
		12-column grid, the cards in `span-8` as their own 12-column grid, and
		the filter in `span-4`.

		**The cards are `span-6` at every width**, so a phone gets two columns
		too — legacy writes it without a breakpoint prefix and an earlier pass
		read it as one column below `sm`.

		The gap is legacy's `grid-gap`: 16px on both axes, 40px on both from
		`bp-md`, which is this project's `lg` (`resources/css/README.md`). The
		row gap used to be left at 16.

		Legacy rendered all of it through a Vue island that POSTed to
		`/api/course/filter` on every click, with the card markup living a second
		time in `filter/components/Card.vue`. Here the page is server-rendered
		and Alpine hides and shows the cards already on it, so changing a filter
		is not a navigation ([[09-public-site]]).

		`courseFilter` holds one value per attribute, seeded from what the server
		filtered by so the two agree on the first paint.
	--}}
	{{-- The trigger lives inside this scope now — it is the filter's own control
	     (`components/course/filter.blade.php`), so it sets `open` directly
	     rather than asking for it through a window event. --}}
	<div
		class="grid grid-cols-12 gap-16 lg:gap-40"
		x-data="courseFilter({{ json_encode($filter->seed()) }})"
	>
		<div class="col-span-12 sm:col-span-8">
			{{-- Covers both an empty catalogue and a filter that matches
			     nothing; the server picks the first state, Alpine the rest. --}}
			<div @class(['hidden' => count($matching) > 0]) :class="{ hidden: count > 0 }">
				Leider keine Kurse gefunden.
			</div>

			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($courses as $course)
					{{-- `data-facets` is the card's own copy of what it can be
					     filtered by, so the browser needs no second payload and
					     no uuid is written down twice. `::class` escapes to a
					     literal `:class` — on a component tag Blade would read
					     one colon as a PHP expression. --}}
					<x-card.course
						:course="$course"
						:eager="$loop->index < 2"
						data-facets="{{ json_encode($facets[$course->uuid]) }}"
						::class="{ hidden: !matches($el) }"
						@class([
							'col-span-6',
							'hidden' => ! in_array($course->uuid, $matching, true),
						])
					/>
				@endforeach
			</div>
		</div>

		<div class="col-span-12 sm:col-span-4">
			<x-course.filter :filter="$filter" :matching="count($matching)" />
		</div>
	</div>
</x-layout.site>
