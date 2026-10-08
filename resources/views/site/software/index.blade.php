<x-layout.site title="Software">
	@php
		$facets = $filter->facets();
		$matching = $filter->matching();
	@endphp

	{{--
		The software list, the shop ([[05-licences]]): the course list's page
		(`site/courses/index.blade.php`) with software cards in it, the same
		grid, the same panel and the same `courseFilter` behind it (Marcel,
		2026-10-08). The filter's attributes are [[SoftwareFilter]]'s.
	--}}
	<div
		class="grid grid-cols-12 gap-16 lg:gap-40"
		x-data="courseFilter({{ json_encode($filter->seed()) }})"
	>
		<div class="col-span-12 sm:col-span-8">
			<div @class(['hidden' => count($matching) > 0]) :class="{ hidden: count > 0 }">
				Leider keine Software gefunden.
			</div>

			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($software as $item)
					<x-card.software
						:software="$item"
						:eager="$loop->index < 2"
						data-facets="{{ json_encode($facets[$item->uuid]) }}"
						::class="{ hidden: !matches($el) }"
						@class([
							'col-span-6',
							'hidden' => ! in_array($item->uuid, $matching, true),
						])
					/>
				@endforeach
			</div>
		</div>

		<div class="col-span-12 sm:col-span-4">
			<x-course.filter :filter="$filter" :matching="count($matching)" :selects="\App\Support\SoftwareFilter::SELECTS" />
		</div>
	</div>
</x-layout.site>
