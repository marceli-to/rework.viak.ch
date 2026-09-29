@props(['booking', 'closed' => false])

{{-- The dashboard's `course/AttendanceBadge.vue`: *Teilnahme offen* until the
     event is closed, then what was recorded when it was closed. --}}
@if (! $closed)
	<x-ui.badge>Teilnahme offen</x-ui.badge>
@elseif ($booking->hasParticipated())
	<x-ui.badge variant="success">Teilgenommen</x-ui.badge>
@else
	<x-ui.badge variant="danger">Nicht teilgenommen</x-ui.badge>
@endif
