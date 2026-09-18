<x-layout.site title="Kurse">
	{{-- The filter trigger sits beside the page title on a phone, as it does on
	     the live site. `courseFilter` holds only whether the panel is open —
	     the filtering itself is a query string. --}}
	<x-slot:actions>
		<button type="button" x-data @click="$dispatch('open-filter')" class="sm:hidden" aria-label="Filter anzeigen">
			<x-icon.filter />
		</button>
	</x-slot:actions>

	{{--
		Rebuilt 1:1 from legacy's `frontend/filter/Index.vue`: an outer
		12-column grid, the cards in `span-8` as their own 12-column grid of
		`span-6` pairs, and the filter in `span-4`.

		Legacy rendered all of it through a Vue island that posted to
		`/api/course/filter` on every click. Here the page is server-rendered and
		the filter is links carrying a query string — so a filtered view is
		linkable, which legacy's was not, and the page works without JavaScript.
	--}}
	<div class="grid grid-cols-12 gap-x-16 gap-y-16 lg:gap-x-40" x-data="{ open: false, close() { this.open = false } }" @open-filter.window="open = true">
		<div class="col-span-12 sm:col-span-8">
			@if ($courses->isEmpty())
				<div>Leider keine Kurse gefunden.</div>
			@else
				<div class="grid grid-cols-12 gap-x-16 gap-y-16 lg:gap-x-40" x-data="{ open: false, close() { this.open = false } }" @open-filter.window="open = true">
					@foreach ($courses as $course)
						<x-site.course-card
							:course="$course"
							:eager="$loop->index < 2"
							class="col-span-12 sm:col-span-6"
						/>
					@endforeach
				</div>
			@endif
		</div>

		<div class="col-span-12 sm:col-span-4">
			<x-site.course-filter :software="$software" :active="$activeSoftware" />
		</div>
	</div>
</x-layout.site>
