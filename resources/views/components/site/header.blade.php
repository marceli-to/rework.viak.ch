@php
	/*
	 * Legacy's nav is Kurse / Experten / Kontakt, plus a basket and a profile
	 * link. Only Kurse has a page in the rework so far — Experten and Kontakt
	 * arrive with chunk 04 — so they are listed here and rendered only when
	 * their route exists. Shipping a nav item that 404s would be worse parity
	 * than shipping one item.
	 */
	$nav = array_filter([
		'Kurse' => \App\Support\SiteUrl::courses(),
		'Experten' => \Illuminate\Support\Facades\Route::has(app()->getLocale().'.experts.index')
			? '/'.app()->getLocale().'/'.\App\Support\SiteUrl::segment('experts') : null,
		'Kontakt' => \Illuminate\Support\Facades\Route::has(app()->getLocale().'.contact')
			? '/'.app()->getLocale().'/'.\App\Support\SiteUrl::segment('contact') : null,
	]);
@endphp

<header class="sticky top-0 z-50 border-b border-line bg-white" x-data="menu">
	<div class="mx-auto flex h-header-xs max-w-(--container-site) items-center justify-between gap-5 px-4 sm:h-header-sm sm:px-8 md:h-header-md">
		<a href="{{ \App\Support\SiteUrl::home() }}" class="text-lg font-semibold tracking-tight text-ink" title="Home">
			VIAK
		</a>

		<nav class="hidden gap-8 text-sm font-medium sm:flex" aria-label="Hauptnavigation">
			@foreach ($nav as $label => $href)
				<a href="{{ $href }}"
					@class(['hover:text-teal-dark', 'text-teal-dark' => request()->is(ltrim($href, '/').'*')])>{{ $label }}</a>
			@endforeach
		</nav>

		<div class="flex items-center gap-4">
			{{-- The count comes from the Alpine store, so it is the same number
			     the basket page shows. `x-cloak` keeps it from flashing 0. --}}
			<a href="#" class="relative hover:text-teal-dark" aria-label="Warenkorb">
				&#128722;
				<template x-if="$store.basket.count > 0">
					<span x-cloak
						class="absolute -top-1.5 -right-2 min-w-4 rounded-full bg-teal px-1 text-center text-[10px] leading-4 font-semibold text-white"
						x-text="$store.basket.count"></span>
				</template>
			</a>

			<a href="/dashboard" class="hover:text-teal-dark" aria-label="Konto">&#128100;</a>

			<button type="button" class="sm:hidden" @click="toggle()" :aria-expanded="open" aria-label="Menü">
				<span x-show="!open">&#9776;</span>
				<span x-show="open" x-cloak>&times;</span>
			</button>
		</div>
	</div>

	<nav x-show="open" x-cloak class="border-t border-line sm:hidden" aria-label="Hauptnavigation, mobil">
		@foreach ($nav as $label => $href)
			<a href="{{ $href }}" class="block border-b border-line px-4 py-3 font-medium hover:text-teal-dark">{{ $label }}</a>
		@endforeach
	</nav>
</header>
