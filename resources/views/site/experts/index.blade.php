{{--
	`web/pages/experts/list.blade.php`, rebuilt 1:1 ([[09-public-site]]).

	Legacy wraps the grid in `<course-filter>`, the course list's Vue island, and
	it draws **nothing** here: no filter column, no trigger. So this is not the
	course list's `span-8` beside a filter but one 12-column grid across the full
	width, measured on production on 2026-09-24 — cards `span-6`, `span-4` from
	sm, and legacy's `grid-gap` of 16px, 40px from `bp-md`.
--}}
<x-layout.site title="Experten" heading="Experten">
	<div class="grid grid-cols-12 gap-16 lg:gap-40">
		@foreach ($experts as $expert)
			<x-site.expert-card :expert="$expert" :eager="$loop->index < 3" class="col-span-6 sm:col-span-4" />
		@endforeach
	</div>
</x-layout.site>
