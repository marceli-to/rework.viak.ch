{{--
	*Über uns* ([[04-content]]) — the 2026-09-23 review's Team mockup, in its
	three parts: the Über uns text, the team, the experts. **The team comes
	before the experts** (VIAK, 2026-11-26; the mockup had them the other way).

	**The mockup's content in the site's look** (Marcel, 2026-10-06): no new
	type, cards or spacing, only blocks the site already has. It opens as
	Kontakt does, then the experts and the team as open collapsibles.

	- *Über uns* is the text that was Kontakt's shut block, moved here whole and
	  drawn like Kontakt's opening block ([[about._intro]]).
	- *Experten* is legacy's Experten page as it was
	  (`web/pages/experts/list.blade.php`), which `/de/experten` now 301s to:
	  cards `span-6`, `span-4` from sm, 16px apart and 40 from lg.
	- *Team* is new. Legacy's team never rendered (its table is empty), so the
	  block shows only once someone is published.

	The first three expert pictures load eagerly only while there is no team
	above them; otherwise they sit below the fold.
--}}
<x-layout.site title="Über uns" heading="Über uns">
	@include('site.about._intro')

	@if ($team->isNotEmpty())
		<x-ui.collapsible title="Team">
			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($team as $member)
					<x-card.team :member="$member" class="col-span-6 sm:col-span-4" />
				@endforeach
			</div>
		</x-ui.collapsible>
	@endif

	<x-ui.collapsible title="Experten" last>
		<div class="grid grid-cols-12 gap-16 lg:gap-40">
			@foreach ($experts as $expert)
				<x-card.expert :expert="$expert" :eager="$team->isEmpty() && $loop->index < 3" class="col-span-6 sm:col-span-4" />
			@endforeach
		</div>
	</x-ui.collapsible>
</x-layout.site>
