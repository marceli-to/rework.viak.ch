@props(['variant' => 'neutral'])

{{--
	The dashboard's `ui/Badge.vue`, for the portals: a state or a count set off
	from the text around it. Keep the two in step — the expert's and the
	student's screens look like the admin's (Marcel, 2026-09-29).

	A 1px border and bold 14px text in the border's colour, square. `solid` is
	the one filled tone: the total in a collapsible title.
--}}
@php
	$tone = match ($variant) {
		'solid' => 'border-gray-600 bg-gray-600 text-white',
		'success' => 'border-success text-success',
		'warning' => 'border-warning text-warning',
		'danger' => 'border-danger text-danger',
		default => 'border-black text-black',
	};
@endphp

<span {{ $attributes->class(['inline-block border px-6 py-2 text-md leading-[1.2] font-bold', $tone]) }}>{{ $slot }}</span>
