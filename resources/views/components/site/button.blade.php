@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

{{--
	`components/buttons/_global.scss` and `_primary.scss`: 14px, 16px from sm,
	bold, 24px of horizontal padding, 28px minimum height, centred.

	**140px minimum width from `sm`** (`%btn`), which only shows on a button that
	is not stretched by its container — every caller until the course page was
	`w-full`, so it had never mattered and was missing.

	`outline` is legacy's `.is-outline` — white, a teal border, teal text, and
	back to regular weight. `secondary` is `.btn-secondary`, the grey the event
	card uses for *Entfernen*; both it and primary go black on hover.
--}}
@php
	$base = 'flex min-h-28 items-center justify-center px-24 text-md font-bold transition-colors sm:min-w-140 sm:text-lg';

	$style = match ($variant) {
		'outline' => 'border border-teal bg-white font-normal text-teal hover:border-black hover:text-black',
		'secondary' => 'bg-gray-400 text-white hover:bg-black',
		default => 'bg-teal text-white hover:bg-black',
	};
@endphp

<{{ $href ? 'a' : 'button' }}
	@if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
	{{ $attributes->class([$base, $style]) }}
>{{ $slot }}</{{ $href ? 'a' : 'button' }}>
