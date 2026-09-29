@props(['event'])

{{--
	Whether a course is happening, on a portal row — as the dashboard says it
	(`course/EventState.vue`): a badge with the dashboard's labels (Marcel,
	2026-09-29: the expert's and the student's screens look like the admin's).
	Until then it was legacy's public half of `EventState.vue`, an italic line
	reading *Kurs ist abgeschlossen*, *Kurs wurde abgesagt*.

	Four states in this order — **closed wins over cancelled, cancelled over
	confirmed** — which matters on the portal, where a student holds seats on
	courses that have already run.

	`x-card.event` on the course page still spells its own two states out
	inline, in legacy's italic: that page is the public site and stays 1:1.
--}}
@php
	$state = match (true) {
		$event->state === \App\Enums\EventState::Closed => ['Kurs abgeschlossen', 'neutral'],
		$event->state === \App\Enums\EventState::Cancelled => ['Kurs abgesagt', 'danger'],
		$event->state === \App\Enums\EventState::Confirmed => ['Kurs findet statt', 'success'],
		default => ['Kurs offen, wird bestätigt', 'warning'],
	};
@endphp

<x-ui.badge :variant="$state[1]">{{ $state[0] }}</x-ui.badge>
