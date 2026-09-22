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

	**The last four are the buttons inside a modal** ([[09-public-site]]), and
	they are not a new design — they are what legacy's own `.btn-primary` and
	`.btn-secondary` become under
	`.notification .notification__inner .notification-actions`, which restates
	their colours at four class selectors deep:

	    .btn-primary   -> background #505050, 1px #505050 border, white text
	    .btn-secondary -> white, 1px #505050 border, #505050 text

	So `gray` and `gray-outline` here. `success` and `success-outline` are
	`.btn-success`, which the same block leaves alone.

	**`gray` and `gray-outline` have no hover, and that is production's
	behaviour rather than an omission.** `.btn-primary:hover` is two classes
	deep and the contextual rule that recoloured it is four, so the black hover
	the rest of the site has never fires inside a notification — an accident of
	specificity that has been on the live site for years. Ported as found; worth
	a decision rather than a quiet fix.
--}}
{{--
	**Full width below `sm`, and that is `width: auto` doing its job** — found
	2026-09-22, measured on the live course page at 500px: legacy's *Entfernen*
	is **461px** there and ours was 95.

	`%btn` is `display: flex` and never declares a width. On a block-level box
	`width: auto` fills the parent, so on a phone — where the twelve columns
	have collapsed into a stack — every button spans its column; at `sm` the
	same button becomes a flex item in a `justify-between` row and shrinks to
	its content with the 140px floor. One declaration, two behaviours.

	A `<button>` does not inherit that: form controls size to `fit-content`
	whatever their `display`, so it shrink-wrapped at every width. `max-sm:`
	rather than `w-full sm:w-auto` because the auth screens pass `w-full`
	deliberately and must keep it at desktop — this adds the phone behaviour and
	touches nothing above it.
--}}
@php
	$base = 'flex min-h-28 items-center justify-center px-24 text-md font-bold transition-colors max-sm:w-full sm:min-w-140 sm:text-lg';

	$style = match ($variant) {
		'outline' => 'border border-teal bg-white font-normal text-teal hover:border-black hover:text-black',
		'secondary' => 'bg-gray-400 text-white hover:bg-black',
		'gray' => 'border border-gray-600 bg-gray-600 text-white',
		'gray-outline' => 'border border-gray-600 bg-white text-gray-600',
		'success' => 'bg-success text-white hover:bg-success-dark',
		'success-outline' => 'border border-success bg-white text-success',
		default => 'bg-teal text-white hover:bg-black',
	};
@endphp

<{{ $href ? 'a' : 'button' }}
	@if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
	{{ $attributes->class([$base, $style]) }}
>{{ $slot }}</{{ $href ? 'a' : 'button' }}>
