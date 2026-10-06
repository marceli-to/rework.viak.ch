{{--
	*Über uns* ([[04-content]]) — the 2026-09-23 review's Team mockup, in its
	three parts and its order: the Über uns text, the experts, the team.

	**The mockup's content in the site's look** (Marcel, 2026-10-06): no new
	type, cards or spacing, only blocks the site already has. Each part is a
	collapsible, as the Kontakt and course pages are made of, all three open.

	- *Über uns* is the text that was Kontakt's shut block, moved here whole.
	- *Experten* is legacy's Experten page as it was
	  (`web/pages/experts/list.blade.php`), which `/de/experten` now 301s to:
	  cards `span-6`, `span-4` from sm, 16px apart and 40 from lg.
	- *Team* is new. Legacy's team never rendered (its table is empty), so the
	  block shows only once someone is published.
--}}
<x-layout.site title="Über uns" heading="Über uns">
	<x-ui.collapsible title="Über uns">
		@include('site.about._intro')
	</x-ui.collapsible>

	<x-ui.collapsible title="Experten" :last="$team->isEmpty()">
		<div class="grid grid-cols-12 gap-16 lg:gap-40">
			@foreach ($experts as $expert)
				<x-card.expert :expert="$expert" :eager="$loop->index < 3" class="col-span-6 sm:col-span-4" />
			@endforeach
		</div>
	</x-ui.collapsible>

	@if ($team->isNotEmpty())
		<x-ui.collapsible title="Team" last>
			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($team as $member)
					<x-card.team :member="$member" class="col-span-6 sm:col-span-4" />
				@endforeach
			</div>
		</x-ui.collapsible>
	@endif
</x-layout.site>
