@props(['variant' => 'error', 'live' => false])

{{--
	`components/_notification.scss:170` — `.notification.is-toast`, the message
	that drops into the top right and goes away when you click it. Legacy uses it
	for a failed login, for "added to basket", and through the booking flow.

	The anchoring is the fiddly part: from `sm` it is not `right: 16px` but
	`calc((100% - 1100px) / 2 + 16px)`, so it lines up with the **container's**
	right edge rather than the window's. Below `sm` it is simply inset 16.

	Sizes are legacy's: 16px, 18px from `bp-md`; 360px wide from `sm` and 480
	from `md`; 8px of padding, 16 left and right from `sm`; the message bold.

	**`live` is the same bar, driven by the store** rather than by the page
	([[09-public-site]]). Legacy splits these across two implementations — the
	Blade partial for a server flash and `vue-toast-notification` for anything
	the browser decides — and `vendor/vue-toast/_main.scss` exists purely to
	make the second look like the first. Measured side by side on production
	they differ by a pixel of padding (`0.5em 1em` against 8/16) and by the
	480px step at `md`, which the vue one never got. One component, so the
	anchoring maths lives in one place and the pixel goes to the Blade
	original's numbers.

	`info` is the grey `#505050` that `$toast-colors` calls `default` — the
	colour both basket messages actually use, because legacy calls
	`$toast.open('…')` without a type.
--}}
@php
	$fill = ['error' => 'bg-danger', 'success' => 'bg-success', 'info' => 'bg-gray-600'];
	$edge = ['error' => 'border-danger', 'success' => 'border-success', 'info' => 'border-gray-600'];

	$position = 'fixed top-16 left-16 z-[1001] w-[calc(100%-32px)] cursor-pointer text-lg text-white sm:top-16 sm:left-auto sm:w-auto sm:max-w-360 sm:right-[calc((100%-1100px)/2+16px)] lg:max-w-480 lg:text-xl';
	$inner = 'flex items-center border p-8 sm:px-16';

	/* The one expression both bindings key off, so the store's variant names
	   and this component's stay one list. */
	$binding = fn (array $map) => '{'.collect($map)
		->map(fn (string $class, string $key) => "'{$class}': \$store.toast.variant === '{$key}'")
		->implode(', ').'}';
@endphp

@if ($live)
	{{-- Empty `x-data`, for the same reason `<x-site.modal>` has one: Alpine 3
	     evaluates a directive only inside a component, `$store` or not. --}}
	<div x-data x-cloak
		x-show="$store.toast.open"
		@click="$store.toast.hide()"
		role="alert"
		class="{{ $position }}"
		:class="{{ $binding($fill) }}">
		<div class="{{ $inner }}" :class="{{ $binding($edge) }}">
			<div class="font-bold" x-text="$store.toast.message"></div>
		</div>
	</div>
@else
	<div x-data="{ open: true }"
		x-show="open"
		@click="open = false"
		role="alert"
		class="{{ $position }} {{ $fill[$variant] ?? $fill['error'] }}">
		<div class="{{ $inner }} {{ $edge[$variant] ?? $edge['error'] }}">
			<div class="font-bold">{{ $slot }}</div>
		</div>
	</div>
@endif
