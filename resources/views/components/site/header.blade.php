@props(['heading' => null])

@php
	/*
	 * `layout/_header.scss` and `menu/_site.scss`, rebuilt 1:1.
	 *
	 * The shape: a 12-column grid with the logo in `span-4` and the menu in
	 * `span-8`, a black rule under the lot from sm up. The menu is itself a
	 * 12-column grid of two `span-6` lists — the nav links spread with
	 * `space-between`, the icons pushed right with `flex-end`.
	 *
	 * All three nav items are always shown, as on the live site. Experten and
	 * Kontakt have no pages in the rework yet (they arrive with chunk 04), so
	 * they point at `#` rather than being hidden — the design is the thing being
	 * matched, and a missing item changes it.
	 */
	$locale = app()->getLocale();

	$nav = [
		['label' => 'Kurse', 'href' => \App\Support\SiteUrl::courses(), 'match' => \App\Support\SiteUrl::segment('course')],
		['label' => 'Experten', 'href' => '#', 'match' => \App\Support\SiteUrl::segment('expert')],
		['label' => 'Kontakt', 'href' => '#', 'match' => \App\Support\SiteUrl::segment('contact')],
	];

	$isHome = request()->getPathInfo() === '/'.$locale;
@endphp

<header class="mb-24 min-h-48 pt-16 sm:min-h-64 sm:pt-28 lg:mb-28 lg:min-h-80" x-data="menu">
	<div class="grid min-h-[inherit] grid-cols-12 gap-x-16 sm:border-b sm:border-black lg:gap-x-40">
		<div class="col-span-12 flex items-start justify-between sm:col-span-4 sm:block">
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				@class(['text-black', 'max-sm:hidden' => ! $isHome])>
				<x-site.icons.logo />
			</a>

			{{-- Mobile: on an inner page the title takes the logo's place, exactly
			     as `site-header__title` does. --}}
			@if (! $isHome && $heading)
				<div class="flex min-h-48 w-full items-end border-b border-black pb-12 sm:hidden">
					<h1 class="text-3xl leading-none">{{ $heading }}</h1>
				</div>
			@endif

			<button type="button" class="p-8 sm:hidden" @click="toggle()" :aria-expanded="open" aria-label="Menü">
				<x-icon.list class="size-24" />
			</button>
		</div>

		{{-- `.site-menu__main` --}}
		<div class="col-span-12 hidden sm:col-span-8 sm:block">
			<nav class="grid h-full grid-cols-12 gap-x-16 lg:gap-x-40" aria-label="Hauptnavigation">
				{{-- `menu/_site.scss`: bold, 24px, 16px at sm, 20px at lg. --}}
				<ul class="col-span-6 flex items-center justify-between text-3xl font-bold sm:text-lg lg:text-2xl">
					@foreach ($nav as $item)
						<li>
							<a href="{{ $item['href'] }}"
								@class(['hover:text-teal', 'text-teal' => request()->is($locale.'/'.$item['match'].'*')])>
								{{ $item['label'] }}
							</a>
						</li>
					@endforeach
				</ul>

				<ul class="col-span-6 flex items-center justify-end gap-24">
					{{-- Hidden while the basket is empty, as legacy hides it with
					     `!hide` when the count is zero. --}}
					<li x-cloak x-show="$store.basket.count > 0">
						<a href="#" class="relative block h-16 w-[19px] hover:text-teal" title="Warenkorb">
							<x-icon.shopping-cart class="block size-full" />
							{{-- `icons/_basket.scss`: a 16px black disc, offset -12/-12. --}}
							<em class="absolute -top-12 -right-12 flex size-16 items-center justify-center rounded-full bg-black text-xs leading-none font-normal text-white not-italic lg:text-sm"
								x-text="$store.basket.count"></em>
						</a>
					</li>
					<li>
						<a href="/dashboard" class="block hover:text-teal" title="Konto">
							<x-icon.user class="size-20" />
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>

	{{--
		Mobile: a full-screen teal panel with an 8px white border, links
		right-aligned over black rules — `menu/_site.scss` under `bp-xs`.
	--}}
	<div x-show="open" x-cloak
		class="fixed inset-0 z-[201] flex flex-col border-8 border-b-0 border-white bg-teal sm:hidden"
		@keydown.escape.window="close()">
		<div class="flex flex-1 flex-col p-8">
			<ul class="mt-72">
				@foreach ($nav as $item)
					<li>
						<a href="{{ $item['href'] }}"
							class="flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold">
							{{ $item['label'] }}
						</a>
					</li>
				@endforeach
			</ul>

			<div class="mt-auto flex justify-end p-8">
				<button type="button" @click="close()" aria-label="Menü schliessen">
					<x-icon.x class="size-32" />
				</button>
			</div>
		</div>
	</div>
</header>
