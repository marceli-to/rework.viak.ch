{{--
	*Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
	Veranstaltungen, nobody picks them ([[HomeController]]). Under a heading
	with the link to the Kurse page beside it, as *Beliebte Angebote* is (an
	open collapsible until 2026-10-07, Marcel), and each one the portal's row (`x-row.event`), which leads with the course: the title
	links to the course, the dates, where and with whom, the fee. No course
	number and no state badge, which are the dashboard's. *Anmelden* goes to
	the course page, where booking already knows about full courses, an
	existing seat and the laptop question.
--}}
<section class="mt-48 lg:mt-64">
	<div class="mb-16 flex flex-wrap items-baseline justify-between gap-x-16 lg:mb-24">
		<h2 class="font-bold">Nächste Kurstermine</h2>
		<a href="{{ \App\Support\SiteUrl::courses() }}" class="inline-flex items-center gap-8 text-teal hover:underline">
			Alle Kurse und Termine
			<x-icon.arrow-right />
		</a>
	</div>

	@foreach ($events as $event)
		<x-row.event :event="$event" :numbered="false" :show-state="false">
			<x-slot:action>
				<x-ui.button href="{{ \App\Support\SiteUrl::course($event->course->getTranslation('slug', app()->getLocale())) }}">Anmelden</x-ui.button>
			</x-slot:action>
		</x-row.event>
	@endforeach
</section>
