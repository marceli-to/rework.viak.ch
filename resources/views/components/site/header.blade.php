@props(['heading' => null])

@php
	/*
	 * `layout/_header.scss`: mb-24 lg:mb-28, pt-16 sm:pt-28, min-height
	 * 48/64/80, and a 12-column inner grid with a black rule under it from sm up.
	 *
	 * Legacy's nav is Kurse / Experten / Kontakt. Only Kurse has a page in the
	 * rework so far — the other two arrive with chunk 04 — so they are listed
	 * here and rendered when their route exists. A nav item that 404s would be
	 * worse parity than one that is briefly absent.
	 */
	$locale = app()->getLocale();
	$nav = array_filter([
		'Kurse' => \Illuminate\Support\Facades\Route::has("{$locale}.courses.index") ? \App\Support\SiteUrl::courses() : null,
		'Experten' => \Illuminate\Support\Facades\Route::has("{$locale}.experts.index")
			? '/'.$locale.'/'.\App\Support\SiteUrl::segment('experts') : null,
		'Kontakt' => \Illuminate\Support\Facades\Route::has("{$locale}.contact")
			? '/'.$locale.'/'.\App\Support\SiteUrl::segment('contact') : null,
	]);
	$isHome = request()->getPathInfo() === '/'.$locale;
@endphp

<header class="mb-24 min-h-48 bg-white pt-16 sm:min-h-64 sm:pt-28 lg:mb-28 lg:min-h-80" x-data="menu">
	<div class="grid min-h-[inherit] grid-cols-12 gap-x-16 gap-y-16 sm:border-b sm:border-primary lg:gap-x-40">
		<div class="col-span-12 sm:col-span-4">
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				@class(['block text-primary', 'max-sm:hidden' => ! $isHome])>
				<x-site.icons.logo />
			</a>

			{{-- Mobile: the page title takes the logo's place on inner pages,
			     exactly as `site-header__title` does. --}}
			@if (! $isHome && $heading)
				<div class="flex min-h-48 items-end border-b border-primary pb-12 sm:hidden">
					<h1 class="text-3xl leading-none text-primary">{{ $heading }}</h1>
				</div>
			@endif
		</div>

		<div class="col-span-12 sm:col-span-8">
			<nav class="flex h-full items-center justify-between" aria-label="Hauptnavigation">
				<ul class="hidden gap-32 sm:flex lg:gap-48">
					@foreach ($nav as $label => $href)
						<li>
							<a href="{{ $href }}"
								@class(['hover:text-secondary', 'text-secondary' => request()->is(ltrim($href, '/').'*')])>{{ $label }}</a>
						</li>
					@endforeach
				</ul>

				{{-- Phosphor, regular weight, for the same reason the dashboard uses
				     it: legacy's icons are hand-drawn one-offs in
				     `web/partials/icons/`, so needing a new one meant drawing it. --}}
				<ul class="ml-auto flex items-center gap-20">
					<li>
						<a href="#" class="relative block hover:text-secondary" aria-label="Warenkorb" title="Warenkorb">
							<x-icon.shopping-cart class="size-20 sm:size-24" />
							<template x-if="$store.basket.count > 0">
								<span x-cloak
									class="absolute -top-4 -right-8 min-w-16 rounded-full bg-secondary px-4 text-center text-xxs leading-16 text-white"
									x-text="$store.basket.count"></span>
							</template>
						</a>
					</li>
					<li>
						<a href="/dashboard" class="block hover:text-secondary" aria-label="Konto" title="Konto">
							<x-icon.user class="size-20 sm:size-24" />
						</a>
					</li>
					<li class="sm:hidden">
						<button type="button" @click="toggle()" :aria-expanded="open" aria-label="Menü">
							<x-icon.list class="size-20" />
						</button>
					</li>
				</ul>
			</nav>
		</div>
	</div>

	{{-- Legacy's mobile menu is a full-screen overlay. --}}
	<div x-show="open" x-cloak
		class="fixed inset-0 z-50 flex flex-col bg-white p-16 sm:hidden"
		@keydown.escape.window="close()">
		<div class="flex items-start justify-between">
			<a href="{{ \App\Support\SiteUrl::home() }}" class="block text-primary"><x-site.icons.logo /></a>
			<button type="button" @click="close()" aria-label="Menü schliessen">
				<x-icon.x class="size-24" />
			</button>
		</div>
		<ul class="mt-40 flex flex-col gap-20 text-3xl">
			@foreach ($nav as $label => $href)
				<li><a href="{{ $href }}" class="hover:text-secondary">{{ $label }}</a></li>
			@endforeach
		</ul>
	</div>
</header>
