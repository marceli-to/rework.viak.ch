@props(['event'])

{{--
	`shared/components/ui/misc/EventState.vue`, the public half.

	Four states in this order — **closed wins over cancelled, cancelled over
	confirmed** — which matters on the portal, where a student holds seats on
	courses that have already run. Legacy's component carries a second set of
	labels for the dashboard (*Kurs abgeschlossen*, *Kurs abgesagt*, …); those
	belong to the Vue side and are not ported here.

	Only *Kurs ist abgeschlossen* has no colour of its own and reads black,
	which is production's choice rather than an omission.

	**At the row's own size**, like the rest of the small print on a
	`.stacked-list` row (Marcel, 2026-09-22). Legacy gives these `.text-xsmall`
	— 12/14/16 against the row's 16/16/18 — so on the live site the one line
	that says whether the course is happening is smaller than the expert's name
	above it. `italic` and nothing else is the only way to inherit: naming a
	Tailwind size drags its own line height along (`resources/css/README.md`).

	`x-card.event` still spells its own two states out inline. It lists
	only bookable events, so *abgeschlossen* and *abgesagt* cannot appear there
	and swapping it to this component would add two branches it has no use for.
--}}
@php
	$state = match (true) {
		$event->state === \App\Enums\EventState::Closed => ['Kurs ist abgeschlossen', ''],
		$event->state === \App\Enums\EventState::Cancelled => ['Kurs wurde abgesagt', 'text-danger'],
		$event->state === \App\Enums\EventState::Confirmed => ['Kurs findet statt', 'text-success'],
		default => ['Kurs offen, wird bestätigt', 'text-warning'],
	};
@endphp

<div {{ $attributes->class([$state[1]]) }}><em class="italic">{{ $state[0] }}</em></div>
