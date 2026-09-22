@props(['href', 'label' => 'Zurück', 'direction' => 'left', 'method' => 'get'])

{{--
	`BackLink.vue` and the logout link beside it, which are the same component
	with the arrow pointing the other way — `icon-arrow-left:below` and
	`icon-arrow-right:below` in `components/icons/_arrow.scss:52`.

	**`:below` is the whole design**: the word is a block, so the arrow drops
	onto its own line underneath rather than sitting beside the text. The class
	does nothing else — `display: inline-block` and a teal hover — which is why
	there is no gap to reproduce.

	Measured against the live stylesheet on 2026-09-22, in the portal's aside:
	**no top margin on a phone, 20px from `sm`, 40px from `lg`**
	(`sm:mt-5x md:mt-10x`). The wrapper carries it, not the link, because the
	link is inline-block and a margin on it would collapse differently.

	**`method="post"` renders a form and a button**, which is what *Logout* has
	to be. Legacy's is `<a href="/logout">`, and Fortify's route is POST only —
	so a link cannot reach it, and a GET that ends a session is something any
	prefetcher can fire. The button is styled to nothing but the tag swap, the
	same way `x-site.button` switches between `<a>` and `<button>`, so the two
	render alike.
--}}
@php
	$tag = $method === 'post' ? 'button' : 'a';
@endphp

<div {{ $attributes->class(['sm:mt-20 lg:mt-40']) }}>
	@if ($method === 'post')
		<form method="POST" action="{{ $href }}" class="inline-block">
			@csrf
	@endif

	<{{ $tag }}
		@if ($method === 'post') type="submit" @else href="{{ $href }}" @endif
		title="{{ $label }}"
		class="inline-block text-left transition-colors hover:text-teal">
		{{-- 4px between the word and the arrow (Marcel, 2026-09-22). Legacy's
		     `:below` sets `display: inline-block` and nothing else, so the two
		     sit flush — a small deliberate departure, and it applies to the
		     profile's *Logout* as well, which is the same control pointing the
		     other way. --}}
		<span class="mb-4 block">{{ $label }}</span>
		@if ($direction === 'left')
			<x-icon.arrow-left />
		@else
			<x-icon.arrow-right />
		@endif
	</{{ $tag }}>

	@if ($method === 'post')
		</form>
	@endif
</div>
