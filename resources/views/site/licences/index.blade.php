<x-layout.site title="Software">
	@php
		$facets = $filter->facets();
		$matching = $filter->matching();
	@endphp

	{{--
		*Software*, the shop ([[05-licences]], [[14-angebote]]): the course
		list's page (`site/courses/index.blade.php`) with a card per licence
		product, the same grid, the same panel and the same `courseFilter`
		behind it. The filter's attributes are [[LicenceFilter]]'s. Until
		2026-10-29 it listed software; the client's two levels put the shop at
		the product level, and the software became the Angebot.
	--}}
	<div
		class="grid grid-cols-12 gap-16 lg:gap-40"
		x-data="courseFilter({{ json_encode($filter->seed()) }})"
	>
		<div class="col-span-12 sm:col-span-8">
			<div @class(['hidden' => count($matching) > 0]) :class="{ hidden: count > 0 }">
				Leider keine Lizenzen gefunden.
			</div>

			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($products as $item)
					<x-card.licence
						:product="$item"
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
			<x-course.filter :filter="$filter" :matching="count($matching)" view="licences" :selects="\App\Support\LicenceFilter::SELECTS" />
		</div>
	</div>
</x-layout.site>
