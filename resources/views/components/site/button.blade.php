@props(['variant' => 'primary', 'href' => null])

{{--
	`components/buttons/_global.scss` and `_primary.scss`: 14px, 16px from sm,
	bold, 24px of horizontal padding, 28px minimum height, centred.

	`outline` is legacy's `.is-outline` — white, a teal border, teal text, and
	back to regular weight.
--}}
@php
	$base = 'flex min-h-28 items-center justify-center px-24 text-md font-bold transition-colors sm:text-lg';

	$style = match ($variant) {
		'outline' => 'border border-teal bg-white font-normal text-teal hover:border-black hover:text-black',
		default => 'bg-teal text-white hover:bg-black',
	};
@endphp

<{{ $href ? 'a' : 'button' }}
	@if ($href) href="{{ $href }}" @else type="button" @endif
	{{ $attributes->class([$base, $style]) }}
>{{ $slot }}</{{ $href ? 'a' : 'button' }}>
