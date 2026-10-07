{{--
	Firmenschulung (`04-content.md`, the review's Firmenschulung mockup) at
	`/de/firmenschulung`; legacy's `/de/individualschulungen` 301s here. Drawn
	as Kontakt is: an opening block, then the enquiry and, once the dashboard
	has picked some, the testimonials, each in an open collapsible as Kontakt's
	form and the course page's *Kundenmeinungen* are. The collapsibles were
	taken out on 2026-10-06 and put back on 2026-10-07 for structure (Marcel).
	Not in the main nav (Marcel, 2026-10-06): Kontakt, the Kurse filter and the
	homepage link here.
--}}
<x-layout.site title="Firmenschulung" heading="Firmenschulung">
	@include('site.training._intro')

	{{-- `#anfrage` is where a sent or refused enquiry lands, so the answer is
	     on screen. Drawn as Kontakt's *Kontaktformular*: a card, the heading
	     in the aside, the form in the column. --}}
	<x-ui.collapsible title="Anfrage" id="anfrage" class="scroll-mt-16" :last="$testimonials->isEmpty()">
		<x-card.text>
			<x-slot:aside>
				<h2>Firmenschulung anfragen</h2>
			</x-slot:aside>

			@include('site.training._form')
		</x-card.text>
	</x-ui.collapsible>

	@if ($testimonials->isNotEmpty())
		<x-ui.collapsible title="Kundenmeinungen" last>
			@include('site.training._testimonials')
		</x-ui.collapsible>
	@endif
</x-layout.site>
