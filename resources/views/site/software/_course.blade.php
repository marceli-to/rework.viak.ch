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

	$facts = [];
	if ($next) {
		$count = max(1, $next->dates->count());
		$where = $next->online ? 'online' : $next->location?->getTranslation('description', $locale, false);
		$facts[] = trim(($count === 1 ? '1 Tag' : "{$count} Tage").($where ? ", {$where}" : ''));
		$facts[] = $when($next);
		// Only while it lies ahead: a deadline passed says nothing to act on.
		if ($next->registration_until?->endOfDay()->isFuture()) {
			$facts[] = 'Anmeldung bis '.$next->registration_until->translatedFormat('j. F');
		}
		if ($more > 0) {
			$facts[] = $more === 1 ? '1 weiteres Datum' : "{$more} weitere Daten";
		}
	} else {
		$facts[] = 'Daten auf Anfrage';
	}

	$fee = $next ? ($next->free_of_charge ? null : $next->fee()) : $course->fee;
	$href = \App\Support\SiteUrl::course($course->getTranslation('slug', $locale));
@endphp

{{--
	One course on an Angebot's page ([[14-angebote]], after `Rhino.html`):
	the title and its price on one line, its next date in one line of facts,
	and the way to the course page, where it is booked (the client,
	2026-10-29). One row per course, not per date: the dates are on the
	course page, and the row says how many more there are.

	Drawn as a licence product is (`card/product.blade.php`): a black rule,
	the title teal and bold. *Anmelden* with a date to book, *Zum Kurs*
	without one, where *Anmelden* would promise what the page cannot do.
--}}
<article class="mt-16 border-t border-black pt-8 sm:mt-32 sm:pt-16">
	<div class="flex flex-wrap items-baseline justify-between gap-x-16">
		<h3 class="font-bold text-teal">
			<a href="{{ $href }}" class="hover:text-black">{{ $course->getTranslation('title', $locale) }}</a>
		</h3>
		@if ($fee !== null && (float) $fee > 0)
			<div class="shrink-0">{{ $chf((string) $fee) }}</div>
		@elseif ($next)
			<div class="shrink-0">kostenlos</div>
		@endif
	</div>

	<div class="mt-8">{{ implode(' · ', $facts) }}</div>

	<div class="mt-16 flex sm:justify-end">
		<x-ui.button :href="$href" class="max-sm:grow">{{ $next ? 'Anmelden' : 'Zum Kurs' }}</x-ui.button>
	</div>
</article>
