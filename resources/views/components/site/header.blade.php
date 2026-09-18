@props(['heading' => null])

@php
	/*
	 * `layout/_header.scss`: mb-6 md:mb-7, pt-4 sm:pt-7, min-height 48/64/80,
	 * and a 12-column inner grid with a black rule under it from sm up.
	 *
	 * Legacy's nav is Kurse / Experten / Kontakt. Only Kurse has a page in the
	 * rework so far — the other two arrive with chunk 04 — so they are listed
	 * here and rendered when their route exists. A nav item that 404s would be
	 * worse parity than one that is briefly absent.
	 */
	$locale = app()->getLocale();
	$nav = array_filter([
		'Kurse' => [\App\Support\SiteUrl::courses(), 'courses'],
		'Experten' => \Illuminate\Support\Facades\Route::has("{$locale}.experts.index")
			? ['/'.$locale.'/'.\App\Support\SiteUrl::segment('experts'), 'experts'] : null,
		'Kontakt' => \Illuminate\Support\Facades\Route::has("{$locale}.contact")
			? ['/'.$locale.'/'.\App\Support\SiteUrl::segment('contact'), 'contact'] : null,
	]);
	$isHome = request()->getPathInfo() === '/'.$locale;
@endphp

<header class="mb-6 min-h-header-xs bg-white pt-4 sm:min-h-header-sm sm:pt-7 md:mb-7 md:min-h-header-md" x-data="menu">
	<div class="grid-12 min-h-[inherit] sm:border-b sm:border-ink">
		<div class="col-span-12 sm:col-span-4">
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				@class(['block text-ink', 'max-sm:hidden' => ! $isHome])>
				<x-site.icons.logo />
			</a>

			{{-- Mobile: the page title takes the logo's place on inner pages,
			     exactly as `site-header__title` does. --}}
			@if (! $isHome && $heading)
				<div class="flex min-h-12 items-end border-b border-ink pb-3 sm:hidden">
					<h1 class="text-2xl leading-none text-ink">{{ $heading }}</h1>
				</div>
			@endif
		</div>

		<div class="col-span-12 sm:col-span-8">
			<nav class="flex h-full items-center justify-between" aria-label="Hauptnavigation">
				<ul class="hidden gap-8 sm:flex md:gap-12">
					@foreach ($nav as $label => [$href, $key])
						<li>
							<a href="{{ $href }}"
								@class(['hover:text-teal', 'text-teal' => request()->is(ltrim($href, '/').'*')])>{{ $label }}</a>
						</li>
					@endforeach
				</ul>

				<ul class="ml-auto flex items-center gap-5">
					<li>
						<a href="#" class="relative block hover:text-teal" aria-label="Warenkorb" title="Warenkorb">
							<x-site.icons.basket />
							<template x-if="$store.basket.count > 0">
								<span x-cloak
									class="absolute -top-1 -right-2 min-w-4 rounded-full bg-teal px-1 text-center text-[10px] leading-4 text-white"
									x-text="$store.basket.count"></span>
							</template>
						</a>
					</li>
					<li>
						<a href="/dashboard" class="block hover:text-teal" aria-label="Konto" title="Konto">
							<x-site.icons.profile />
						</a>
					</li>
					<li class="sm:hidden">
						<button type="button" @click="toggle()" :aria-expanded="open" aria-label="Menü">
							<x-site.icons.burger />
						</button>
					</li>
				</ul>
			</nav>
		</div>
	</div>

	{{-- Legacy's mobile menu is a full-screen overlay. --}}
	<div x-show="open" x-cloak
		class="fixed inset-0 z-50 flex flex-col bg-white p-4 sm:hidden"
		@keydown.escape.window="close()">
		<div class="flex items-start justify-between">
			<a href="{{ \App\Support\SiteUrl::home() }}" class="block text-ink"><x-site.icons.logo /></a>
			<button type="button" @click="close()" aria-label="Menü schliessen" class="text-3xl leading-none">&times;</button>
		</div>
		<ul class="mt-10 flex flex-col gap-5 text-2xl">
			@foreach ($nav as $label => [$href, $key])
				<li><a href="{{ $href }}" class="hover:text-teal">{{ $label }}</a></li>
			@endforeach
		</ul>
	</div>
</header>
