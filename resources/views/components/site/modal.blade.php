@props([
	'variant' => 'info',
	'message' => null,
	'show' => 'false',
	'close' => '',
	'wide' => false,
])

{{--
	`components/_notification.scss:69` — `.notification.is-modal`, the dialog
	legacy asks its questions in: *Computer mieten*, *Der Kurs wurde im Warenkorb
	abgelegt*, and every alert behind a `<notification>` tag.

	**Measured, not read.** The stylesheet and the browser disagree about this
	one, and the browser wins: `.notification.is-modal` extends `%lightbox`,
	whose `> div` carries `min-width: 600px` from `bp-sm` up, while the modal's
	own rules set `max-width: 360px` and then `480px !important`. A min-width
	always beats a max-width, so **the box is 600px wide on production** and the
	480 never happens. Anyone porting from the SCSS would build it 480 and
	wonder why it looked wrong.

	The rest, from `getComputedStyle` on the live site at 2026-09-22 with
	legacy's own markup rendered into the page:

	| | |
	|---|---|
	| overlay | `rgba(255,255,255,.9)`, `z-index: 1001`, centred, scrolls |
	| box | 600px from `sm`, 80% of the window below it |
	| box padding | 24/16, then 32/24 from `lg` |
	| type | 16px, 20px from `lg`, both at line height 1.3 |
	| border | **3px**, in the variant's colour |
	| message | bold, centred |
	| text | 16px whatever the box is, 16px above it |
	| actions | 32px above, stacked, 12px apart |

	Legacy's `sm` is 700 and ours is 640 ([[00-foundation]]), so the 600px box
	arrives 60px earlier and sits in a 640px window with 20px either side rather
	than 50. That is the breakpoint decision playing out, not a porting error.

	**The backdrop closes it — a deliberate departure.** `Notification.vue` has
	an `addListeners()` that wires exactly this and is never called, so on
	production only Escape works. The overlay still says `cursor: pointer` and
	the box `cursor: default`, which is the dead code advertising itself; the
	rental dialog has no close button at all, so a visitor who wants neither
	answer has only the Escape key. Honouring what the cursor promises.
--}}
@php
	$tone = match ($variant) {
		'success' => ['text' => 'text-success', 'border' => 'border-success'],
		'error' => ['text' => 'text-danger', 'border' => 'border-danger'],
		default => ['text' => 'text-gray-600', 'border' => 'border-gray-600'],
	};
@endphp

{{-- `x-data` with nothing in it is load-bearing: Alpine 3 only walks directives
     inside a component, so an `x-show` that reads only `$store` still needs a
     scope to be evaluated in. Without it the element keeps `x-cloak` and never
     appears — silently, because nothing errors. --}}
<div x-data x-cloak x-show="{{ $show }}"
	@class([
		'fixed inset-0 z-[1001] flex cursor-pointer items-center justify-center overflow-y-auto bg-white/90 text-lg leading-[1.3] lg:text-2xl',
		$tone['text'],
	])
	@if ($close)
		@click.self="{{ $close }}"
		@keydown.escape.window="{{ $close }}"
	@endif
	role="dialog" aria-modal="true">

	{{-- `w-600` rather than `min-w-600` with a `max-w` that loses to it: the
	     measured outcome is a box of exactly 600, and saying so beats
	     reproducing the collision. --}}
	<div {{ $attributes->class([
		'flex max-w-[80%] cursor-default flex-col items-center border-[3px] bg-white px-16 py-24 sm:w-600 sm:max-w-none lg:px-24 lg:py-32',
		$tone['border'],
	]) }}>

		@if ($message)
			<div class="text-center font-bold">{{ $message }}</div>
		@endif

		{{-- 16px here whatever the box is doing — `.notification-text` sets its
		     own size, so the `lg:text-2xl` above stops at the message. --}}
		@isset($text)
			<div class="mt-16 text-center text-lg leading-[1.3]">{{ $text }}</div>
		@endisset

		{{--
			`.notification-actions` is a shrink-to-fit column inside a centred
			column, so **it is as wide as its widest button and every button
			fills it** — `.btn-primary` carries `width: 100%`. The 240px cap is
			the actions block's own (`a[class^="btn-"] { max-width: 240px }`),
			and legacy drops it with `!max-w-none` on the rental dialog, whose
			*Nein, ich bringe meinen eigenen Laptop* would otherwise wrap onto
			two lines. That is what `wide` is.
		--}}
		@isset($actions)
			<div @class([
				'mt-32 flex flex-col items-center [&>*]:w-full [&>*+*]:mt-12',
				'[&>*]:max-w-240' => ! $wide,
			])>{{ $actions }}</div>
		@endisset

		{{ $slot }}
	</div>
</div>
