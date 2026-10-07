{{--
	*Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
	Veranstaltungen, nobody picks them ([[HomeController]]). An open
	collapsible, as the course page lists its dates, and each one the
	portal's row (`x-row.event`), which leads with the course: the title
	links to the course, the dates, where and with whom, the fee. No course
	number and no state badge, which are the dashboard's. *Anmelden* goes to
	the course page, where booking already knows about full courses, an
	existing seat and the laptop question.
--}}
@foreach ($events as $event)
	<x-row.event :event="$event" :numbered="false" :show-state="false">
		<x-slot:action>
			<x-ui.button href="{{ \App\Support\SiteUrl::course($event->course->getTranslation('slug', app()->getLocale())) }}">Anmelden</x-ui.button>
		</x-slot:action>
	</x-row.event>
@endforeach

<a href="{{ \App\Support\SiteUrl::courses() }}" class="mt-32 inline-flex items-center gap-8 text-teal hover:underline">
	Alle Kurse und Termine
	<x-icon.arrow-right />
</a>
