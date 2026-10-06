{{--
	Firmenschulung (`04-content.md`, the review's Firmenschulung mockup) at
	`/de/firmenschulung`; legacy's `/de/individualschulungen` 301s here. Drawn
	as Kontakt is (Marcel, 2026-10-06): an opening block, then open
	collapsibles, *Anfrage* and, once the dashboard has picked some,
	*Kundenmeinungen*. Not in the main nav; Kontakt links here.
--}}
<x-layout.site title="Firmenschulung" heading="Firmenschulung">
	@include('site.training._intro')

	{{-- `#anfrage` is where a sent or refused enquiry lands. --}}
	<x-ui.collapsible title="Anfrage" id="anfrage" class="scroll-mt-16" :last="$testimonials->isEmpty()">
		@include('site.training._form')
	</x-ui.collapsible>

	@if ($testimonials->isNotEmpty())
		<x-ui.collapsible title="Kundenmeinungen" last>
			@include('site.training._testimonials')
		</x-ui.collapsible>
	@endif
</x-layout.site>
