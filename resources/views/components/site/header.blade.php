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
		<div class="col-span-12 sm:col-span-4">
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				@class(['text-black', 'max-sm:hidden' => ! $isHome])>
				<x-site.icons.logo />
			</a>

			{{-- Mobile: on an inner page the title takes the logo's place, exactly
			     as `site-header__title` does. A page can put a control beside it —
			     the course list puts its filter trigger there. --}}
			@if (! $isHome && $heading)
				<div class="flex min-h-48 w-full items-end justify-between border-b border-black pb-12 sm:hidden">
					<h1 class="text-3xl leading-none">{{ $heading }}</h1>
					{{ $slot }}
				</div>
			@endif
		</div>

		{{--
			`.site-menu__main` — a 12-column grid of two `span-6` lists.

			**Top-aligned, not centred.** Measured on production: the menu sits at
			the very top of the 80px header row (its box is 28→54px, the row is
			28→108px) while the logo has a 4px top margin and hangs below it. An
			earlier pass centred it, which is what put the items in the wrong place.
		--}}
		<div class="col-span-12 hidden sm:col-span-8 sm:block">
			<nav class="grid grid-cols-12 gap-x-16 lg:gap-x-40" aria-label="Hauptnavigation">
				{{-- `menu/_site.scss`: bold, 24px, 16px at sm, 20px at lg. The three
				     links fill the full `span-6` — on production Kurse begins at the
				     column's left edge and Kontakt ends exactly at its right. --}}
				<ul class="col-span-6 flex justify-between text-3xl font-bold sm:text-lg lg:text-2xl">
					@foreach ($nav as $item)
						<li class="flex items-center">
							<a href="{{ $item['href'] }}"
								@class(['hover:text-teal', 'text-teal' => request()->is($locale.'/'.$item['match'].'*')])>
								{{ $item['label'] }}
							</a>
						</li>
					@endforeach
				</ul>

				<ul class="col-span-6 flex justify-end">
					{{-- Hidden while the basket is empty, as legacy hides it with
					     `!hide` at a count of zero — which is why the live header
					     usually shows only the account icon. --}}
					<li class="flex items-center" x-cloak x-show="$store.basket.count > 0">
						<a href="#" class="relative block h-16 w-19 hover:text-teal" title="Warenkorb">
							<x-icon.basket class="block w-full!" />
							{{-- `icons/_basket.scss`: a 16px black disc, offset -12/-12. --}}
							<em class="absolute -top-12 -right-12 flex size-16 items-center justify-center rounded-full bg-black text-xs leading-none font-normal text-white not-italic lg:text-sm"
								x-text="$store.basket.count"></em>
						</a>
					</li>
					<li class="flex items-center sm:ml-16 lg:ml-32">
						<a href="/dashboard" class="block hover:text-teal" title="Profil">
							{{-- `icons/_profile.scss` sizes this by **height** — 16px, 20px from
							     the desktop breakpoint — and lets the width follow. --}}
							<x-icon.profile class="h-16 w-auto! lg:h-20" />
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>

	{{--
		`menu/_site.scss` under `bp-xs`: a teal panel behind an 8px white frame
		that stops short of the bottom, links right-aligned over black rules, and
		a white bar carrying the social links and the close control.

		`Profil` joins the list here — on mobile legacy shows it as a word rather
		than the icon it uses on desktop.
	--}}
	<div x-show="open" x-cloak
		class="fixed inset-0 z-[201] flex flex-col border-8 border-b-0 border-white bg-teal sm:hidden"
		@keydown.escape.window="close()">
		<div class="flex-1 p-8">
			<ul class="mt-72">
				@foreach ([...$nav, ['label' => 'Profil', 'href' => '/dashboard', 'match' => 'dashboard']] as $item)
					<li>
						<a href="{{ $item['href'] }}"
							class="flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold hover:text-white">
							{{ $item['label'] }}
						</a>
					</li>
				@endforeach
			</ul>
		</div>

		<footer class="flex h-64 items-center justify-between bg-white px-8">
			<div class="flex items-center gap-16">
				<a href="mailto:hallo@visualisierungs-akademie.ch" title="Kontakt"><x-icon.mail /></a>
				<a href="https://www.instagram.com/viak.ch/" target="_blank" rel="noopener" title="Instagram"><x-icon.instagram /></a>
				<a href="https://www.facebook.com/ViAkSchweiz" target="_blank" rel="noopener" title="Facebook"><x-icon.facebook /></a>
			</div>
			<button type="button" @click="close()" aria-label="Menü schliessen" class="mr-12">
				<x-icon.cross size="large" />
			</button>
		</footer>
	</div>

	{{-- `icons/_menu.scss`: the burger is **fixed at the bottom right**, 32×24,
	     not in the header bar. --}}
	<button type="button" x-show="! open"
		class="fixed right-20 bottom-20 z-[99] h-24 w-32 sm:hidden"
		@click="toggle()" :aria-expanded="open" aria-label="Menü">
		<x-icon.burger class="w-full!" />
	</button>

</header>
