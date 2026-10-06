{{--
	Firmenschulung (`04-content.md`, the review's Firmenschulung mockup) at
	`/de/firmenschulung`; legacy's `/de/individualschulungen` 301s here. Drawn
	as Kontakt is (Marcel, 2026-10-06): an opening block with the enquiry form
	at the end of its copy and, once the dashboard has picked some, the
	testimonials as cards. No collapsibles. Not in the main nav (Marcel,
	2026-10-06): Kontakt, the Kurse filter and the homepage link here.
--}}
<x-layout.site title="Firmenschulung" heading="Firmenschulung">
	@include('site.training._intro')

	@if ($testimonials->isNotEmpty())
		@include('site.training._testimonials')
	@endif
</x-layout.site>
