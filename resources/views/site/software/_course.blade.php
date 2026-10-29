@php
	$locale = app()->getLocale();
	$next = $course->events->first(fn ($event) => $event->isBookable());
	$more = $course->events->filter(fn ($event) => $event->isBookable())->count() - 1;
	$chf = fn (string $amount) => 'CHF '.number_format((float) $amount, 2, '.', "'");

	// *16. bis 17. November 2026*: the first and the last day, the month and
	// year said once where the two share them.
	$when = function ($event): string {
		$days = $event->dates->pluck('date')->filter()->sort()->values();
		$first = $days->first() ?? $event->date;
		$last = $days->last() ?? $event->date;

		return match (true) {
			$first->isSameDay($last) => $first->translatedFormat('j. F Y'),
			$first->isSameMonth($last) => $first->translatedFormat('j.').' bis '.$last->translatedFormat('j. F Y'),
			$first->isSameYear($last) => $first->translatedFormat('j. F').' bis '.$last->translatedFormat('j. F Y'),
			default => $first->translatedFormat('j. F Y').' bis '.$last->translatedFormat('j. F Y'),
		};
	};

	$where = $next ? ($next->online ? 'Onlinekurs' : $next->location?->getTranslation('description', $locale, false)) : null;
	$days = $next ? max(1, $next->dates->count()) : null;
	// Only while it lies ahead: a deadline passed says nothing to act on.
	$deadline = $next?->registration_until?->endOfDay()->isFuture() ? $next->registration_until : null;

	$fee = $next ? ($next->free_of_charge ? null : $next->fee()) : $course->fee;
	$href = \App\Support\SiteUrl::course($course->getTranslation('slug', $locale));
@endphp

{{--
	One course on an Angebot's page ([[14-angebote]]), **drawn as a date row
	on the course page** (`card/event.blade.php`, Marcel 2026-10-29, after a
	first version in two columns side by side): the same black rule, type and
	three `span-4` columns. The course and its next date; where, how long, the
	deadline while ahead and how many more dates; the price and the way to
	the course page, where it is booked (the client, 2026-10-29). One row per
	course, not per date (#51).

	*Anmelden* with a date to book, *Zum Kurs* without one, where *Anmelden*
	would promise what the page cannot do; then the course fee and *Daten auf
	Anfrage*.
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- Which course, and when --}}
		<div class="sm:col-span-4">
			<a href="{{ $href }}" class="font-bold text-teal hover:text-black">{{ $course->getTranslation('title', $locale) }}</a>
			<div>{{ $next ? $when($next) : 'Daten auf Anfrage' }}</div>
		</div>

		{{-- Where, how long, until when --}}
		<div class="max-sm:mt-8 sm:col-span-4">
			@if ($next)
				<div>{{ $days === 1 ? '1 Tag' : "{$days} Tage" }}@if ($where), {{ $where }}@endif</div>
				@if ($deadline)
					<div><em class="italic">Anmeldung möglich bis {{ $deadline->format('d.m.Y') }}</em></div>
				@endif
				@if ($more > 0)
					<div><em class="italic">{{ $more === 1 ? '1 weiteres Datum' : "{$more} weitere Daten" }}</em></div>
				@endif
			@endif
		</div>

		{{-- What it costs, and the way on --}}
		<div class="mt-16 flex items-start justify-between gap-16 sm:col-span-4 sm:mt-0">
			<div class="shrink-0 whitespace-nowrap">
				@if ($fee !== null && (float) $fee > 0)
					{{ $chf((string) $fee) }}
				@elseif ($next)
					kostenlos
				@endif
			</div>

			<x-ui.button :href="$href">{{ $next ? 'Anmelden' : 'Zum Kurs' }}</x-ui.button>
		</div>
	</div>
</article>
