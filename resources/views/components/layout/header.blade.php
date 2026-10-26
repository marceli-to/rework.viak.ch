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
	 * All four nav items are always shown.
	 */
	$locale = app()->getLocale();

	/*
	 * **Matched by route name, not by path prefix** — legacy's own
	 * `request()->routeIs('*.page.course*')`, and the difference is not
	 * academic: the expert portal is `/de/experte/profil`, which begins with the
	 * `experte` segment, so a `request()->is('de/experte*')` lit *Experten* on
	 * every screen of that portal (found 2026-09-22, in the browser — the route
	 * did not exist when the header was built). Legacy is immune because its
	 * portal route is named `de.page.expert.profile` and its pattern is
	 * `page.expert` exactly.
	 */
	$nav = [
		['label' => 'Kurse', 'href' => \App\Support\SiteUrl::courses(), 'match' => "{$locale}.courses.*"],
		// The software list, the shop, and each software's page ([[05-licences]]).
		['label' => 'Software', 'href' => \App\Support\SiteUrl::softwareIndex(), 'match' => "{$locale}.software.*"],
		// *Über uns* took Experten's place (2026-10-06), and an expert's own page
		// still lights it: the experts are on *Über uns* now.
		['label' => 'Über uns', 'href' => \App\Support\SiteUrl::about(), 'match' => ["{$locale}.about", "{$locale}.experts.*"]],
		['label' => 'Kontakt', 'href' => \App\Support\SiteUrl::contact(), 'match' => "{$locale}.contact"],
	];

	$isHome = request()->getPathInfo() === '/'.$locale;

	// The areas this person may use; more than one makes the profile icon a
	// menu of them, the rework's role picker ([[SiteUrl::areasFor]]).
	$areas = \App\Support\SiteUrl::areasFor(auth()->user(), $locale);
	$areaActive = fn (array $area) => $area['key'] !== 'admin' && request()->routeIs("{$locale}.{$area['key']}.*");
@endphp

<header class="mb-24 min-h-48 pt-16 sm:min-h-64 sm:pt-28 lg:mb-28 lg:min-h-80" x-data="menu">
	{{-- `siteSearch` around the row, so the magnifier and its panel share it,
	     and a click anywhere else in the page shuts the panel ([[15-search]]). --}}
	<div class="relative grid min-h-[inherit] grid-cols-12 gap-x-16 sm:border-b sm:border-black lg:gap-x-40"
		x-data="siteSearch(@js(\App\Support\SiteUrl::search()))" @click.outside="close()" @keydown.escape.window="close()">
		<div class="col-span-12 sm:col-span-4">
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				@class(['text-black', 'max-sm:hidden' => ! $isHome])>
				<x-icon.logo />
			</a>

			{{-- Mobile: on an inner page the title takes the logo's place, exactly
			     as `site-header__title` does — which holds the `h1` and **nothing
			     else**. An earlier pass gave it a slot so the course list could
			     put its filter trigger beside the title; legacy's trigger is
			     `position: fixed` and merely overlays that spot, so it belongs to
			     the filter rather than to the header ([[09-public-site]]). --}}
			@if (! $isHome && $heading)
				<div class="flex min-h-48 w-full items-end border-b border-black pb-12 sm:hidden">
					<h1 class="text-3xl leading-none font-bold">{{ $heading }}</h1>
				</div>
			@endif
		</div>

		{{--
			`.site-menu__main` — a 12-column grid of two `span-6` lists.

			**Top-aligned, not centred.** Measured on production: the menu sits at
			the very top of the 80px header row (its box is 28→54px, the row is
			28→108px) while the logo has a 4px top margin and hangs below it. An
			earlier pass centred it, which is what put the items in the wrong place.

			From sm, as legacy. For a few hours on 2026-10-06 it started at md,
			while Firmenschulung was a fifth link and five did not fit beside the
			fixed 199px logo below ~760px. Firmenschulung came out of the menu
			again (Marcel: the homepage, the course pages and Kontakt link to it).
		--}}
		<div class="col-span-12 hidden sm:col-span-8 sm:block">
			<nav class="grid grid-cols-12 gap-x-16 lg:gap-x-40" aria-label="Hauptnavigation">
				{{-- `menu/_site.scss`: bold, 24px, 16px at sm, 20px at lg. Legacy's
				     three links fill a `span-6` — on production Kurse begins at the
				     column's left edge and Kontakt ends exactly at its right.

				     **Four do not fit in it** (Software joined, 2026-10-06): the list
				     overflows a span-6 and *Über uns* breaks onto two lines. So the links
				     take `span-10` (`span-9` from lg), the icons, which need little, the
				     rest, and each link stays on one line. `gap-x-6` is only a floor:
				     `justify-between` spreads them, 25px apart at 640, the tightest. --}}
				<ul class="col-span-10 flex justify-between gap-x-6 text-3xl font-bold whitespace-nowrap sm:text-lg lg:col-span-9 lg:text-2xl">
					@foreach ($nav as $item)
						<li class="flex items-center">
							<a href="{{ $item['href'] }}"
								@class(['hover:text-teal', 'text-teal' => request()->routeIs($item['match'])])>
								{{ $item['label'] }}
							</a>
						</li>
					@endforeach
				</ul>

				<ul class="col-span-2 flex justify-end lg:col-span-3">
					{{-- Hidden while the basket is empty, as legacy hides it with
					     `!hide` at a count of zero — which is why the live header
					     usually shows only the account icon. --}}
					{{-- The site search ([[15-search]]): the panel below opens under the
					     header's rule. Proposed 2026-10-26; the mockup's grey input does
					     not fit beside four 24px links, an icon does. Sized as the
					     profile icon is, by height. --}}
					<li class="flex items-center">
						<button type="button" class="block hover:text-teal" title="Suche" aria-label="Suche" :aria-expanded="open" :class="open && 'text-teal'" @click="toggle()">
							<x-icon.search class="h-16 w-auto! lg:h-20" />
						</button>
					</li>
					<li class="flex items-center sm:ml-16 lg:ml-32" x-cloak x-show="$store.basket.count > 0">
						{{-- `icons/_basket.scss` sizes the **anchor**, not the svg, and
						     it has two steps: 19×16 from `bp-sm` and **26×22 from
						     `bp-md`** — which is our `lg`. Only the first was ported,
						     so the icon stayed a quarter too small on every desktop
						     (Marcel, 2026-09-22). The svg is `width: 100%; height:
						     auto` and follows. --}}
						<a href="{{ \App\Support\SiteUrl::checkout('basket') }}"
							class="relative block h-16 w-19 hover:text-teal lg:h-22 lg:w-26" title="Warenkorb">
							<x-icon.basket class="block w-full!" />
							{{-- The badge steps with it: a 16px black disc at -12/-12,
							     18px at -14/-14 from `lg`. --}}
							<em class="absolute -top-12 -right-12 flex size-16 items-center justify-center rounded-full bg-black text-xs leading-none font-normal text-white not-italic lg:-top-14 lg:-right-14 lg:size-18 lg:text-sm"
								x-text="$store.basket.count"></em>
						</a>
					</li>
					<li class="flex items-center sm:ml-16 lg:ml-32">
						{{-- Where this goes depends on who is looking, as
						     legacy's `MenuItemProfile` does it — and a **guest
						     goes to the login**, which is the case that was
						     missing: it pointed at `/dashboard` for everyone,
						     so a signed-out visitor clicking *Profil* landed on
						     the admin SPA shell ([[SiteUrl::profileFor]]). --}}
						@if (count($areas) > 1)
							{{-- Several areas: the icon opens them, as the dashboard's
							     burger opens its panel — white, a black rule, bold and
							     right-aligned, the area you are in teal. Legacy asked
							     once, on a screen after login ([[SiteUrl::areasFor]]). --}}
							<div class="relative" x-data="{ areas: false }" @click.outside="areas = false" @keydown.escape="areas = false">
								<button type="button" class="block hover:text-teal" title="Profil" aria-haspopup="true" :aria-expanded="areas" @click="areas = ! areas">
									<x-icon.profile class="h-16 w-auto! lg:h-20" />
								</button>
								<ul x-show="areas" x-cloak
									class="absolute top-full right-0 z-[201] mt-12 w-200 border border-black bg-white px-16 py-8 text-right text-lg font-bold lg:text-2xl">
									@foreach ($areas as $area)
										<li>
											<a href="{{ $area['href'] }}"
												@class(['block py-8 hover:text-teal', 'text-teal' => $areaActive($area)])>{{ $area['label'] }}</a>
										</li>
									@endforeach
								</ul>
							</div>
						@else
							<a href="{{ \App\Support\SiteUrl::profileFor(auth()->user()) }}" class="block hover:text-teal" title="Profil">
								{{-- `icons/_profile.scss` sizes this by **height** — 16px, 20px from
								     the desktop breakpoint — and lets the width follow. --}}
								<x-icon.profile class="h-16 w-auto! lg:h-20" />
							</a>
						@endif
					</li>
				</ul>
			</nav>
		</div>
		{{--
			The search panel ([[15-search]]): white under the header's black rule,
			its own rule at the bottom; Kontakt's two columns, the heading in the
			aside and the field in the copy column, the hits as the site's list rows. The field is a
			form field as everywhere on the site, bold teal on a black rule.
			Enter opens the marked hit, or the result page.
		--}}
		<div x-show="open" x-cloak
			class="absolute inset-x-0 top-full z-[150] mt-px grid grid-cols-12 gap-x-16 border-b border-black bg-white pt-24 pb-32 max-sm:hidden lg:gap-x-40 lg:pt-32 lg:pb-48">
			{{-- As Kontakt has it: the heading bold teal in the aside, the field
			     and its hits in the copy column (Marcel, 2026-10-26). --}}
			<label for="site-search" class="col-span-4 font-bold text-teal">Kurs oder Software suchen</label>
			<form class="col-span-8" role="search" action="{{ \App\Support\SiteUrl::search() }}" @submit.prevent="go()">
				<input id="site-search" x-ref="field" name="q" type="search" autocomplete="off" spellcheck="false"
					x-model="query" @input="type()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
					role="combobox" aria-controls="site-search-hits" aria-autocomplete="list"
					:aria-expanded="hits.length > 0" :aria-activedescendant="active >= 0 ? `site-search-hit-${active}` : null"
					class="block w-full border-b border-black bg-transparent py-4 text-lg leading-[normal] font-bold text-teal outline-hidden sm:text-xl lg:text-3xl [&::-webkit-search-cancel-button]:appearance-none">

				{{-- The field's rule is the first row's: no gap, no second line (Marcel, 2026-10-26). --}}
				<ul id="site-search-hits" role="listbox" class="[&>li:first-of-type>a]:border-t-0" x-show="hits.length">
					<template x-for="(hit, index) in hits" :key="hit.url + hit.title">
						<li :id="`site-search-hit-${index}`" role="option" :aria-selected="index === active">
							<a :href="hit.url" class="group block border-t border-black pt-8 pb-16 sm:pt-12" @mouseenter="active = index">
								<span class="flex items-start justify-between gap-16">
									<span class="font-bold group-hover:text-teal" :class="index === active && 'text-teal'" x-text="hit.title"></span>
									<span class="mt-2 inline-block shrink-0 border border-black px-6 py-2 text-md leading-[1.2] font-bold" x-text="hit.label"></span>
								</span>
								<span x-show="hit.lead" class="mt-4 block text-md text-gray-600 sm:text-lg lg:text-xl" x-text="hit.lead && hit.lead.length > 140 ? hit.lead.slice(0, 140) + '…' : hit.lead"></span>
							</a>
						</li>
					</template>
				</ul>

				<a x-show="hits.length" :href="resultsFor(query)" class="mt-8 inline-flex flex-col items-start hover:text-teal">
					<span>Alle Ergebnisse für «<span x-text="query.trim()"></span>»</span>
					<x-icon.arrow-right class="mt-8" />
				</a>

				<p x-show="!loading && answered !== '' && answered === query.trim() && !hits.length" class="mt-16 text-md italic sm:mt-24 sm:text-lg lg:text-xl">
					Nichts gefunden für «<span x-text="answered"></span>».
				</p>
			</form>
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
			{{-- The logo heads the panel, as `menu.blade.php` has it: the link
			     comes before `.site-menu__main`, inside the 8px padding, and the
			     72px below is the `ul`'s own margin rather than a gap between
			     them. An earlier pass kept the margin and dropped the logo. --}}
			<a href="{{ \App\Support\SiteUrl::home() }}"
				title="Home | {{ config('app.name') }}"
				class="block text-black"
				@click="close()">
				<x-icon.logo />
			</a>

			{{--
				`menu.blade.php` is **two `<ul>`s** inside `.site-menu__main` —
				the three nav links, then basket and profile. On desktop they
				are the two `span-6` lists above; on a phone they stack into one
				list, which is why *Warenkorb* belongs here between Kontakt and
				Profil. It was missing (found 2026-09-22).
			--}}
			{{-- The search as the list's first row, a plain form to the result
			     page: on a phone the panel's live list would sit under the keyboard
			     ([[15-search]]). No rule of its own; *Kurse*'s is under it. --}}
			<form class="mt-72" role="search" action="{{ \App\Support\SiteUrl::search() }}">
				<label for="menu-search" class="sr-only">Suche</label>
				<input id="menu-search" name="q" type="search" autocomplete="off" placeholder="Suchen"
					class="block min-h-48 w-full bg-transparent text-right text-3xl font-bold outline-hidden placeholder:text-black/50 [&::-webkit-search-cancel-button]:appearance-none">
			</form>

			<ul>
				@foreach ($nav as $item)
					<li>
						<a href="{{ $item['href'] }}"
							class="flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold hover:text-white">
							{{ $item['label'] }}
						</a>
					</li>
				@endforeach

				{{-- The whole item is hidden at a count of zero — legacy's
				     `!hide` on the `<li>`, the same rule as the desktop icon.

				     **The badge is a different animal down here**: 26×26 rather
				     than 16, `margin-left: 12px` instead of an offset, and
				     **teal on black** rather than white on black
				     (`icons/_basket.scss` under `bp-xs`). It is beside the word
				     rather than hanging off an icon, so it is read, not
				     glanced at. --}}
				<li x-cloak x-show="$store.basket.count > 0">
					<a href="{{ \App\Support\SiteUrl::checkout('basket') }}"
						class="flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold hover:text-white">
						Warenkorb
						<em class="ml-12 flex size-26 items-center justify-center rounded-full bg-black text-lg leading-none font-bold text-teal not-italic"
							x-text="$store.basket.count"></em>
					</a>
				</li>

				{{-- Several areas are several items here, where the one word was;
				     one area keeps *Profil*. --}}
				@if (count($areas) > 1)
					@foreach ($areas as $area)
						<li>
							<a href="{{ $area['href'] }}"
								@class(['flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold hover:text-white', 'text-white' => $areaActive($area)])>
								{{ $area['label'] }}
							</a>
						</li>
					@endforeach
				@else
					<li>
						<a href="{{ \App\Support\SiteUrl::profileFor(auth()->user()) }}"
							class="flex min-h-48 items-center justify-end border-t border-black text-3xl font-bold hover:text-white">
							Profil
						</a>
					</li>
				@endif
			</ul>
		</div>

		{{-- `.site-menu__footer` has **no padding of its own**. The 8px on the
		     left is each icon's `mx-2x`, and the right is the cross's `mr-3x` —
		     12px, not 20. Padding the footer instead pushed the cross 8px in. --}}
		<footer class="flex h-64 items-center justify-between bg-white">
			<div class="ml-8 flex items-center gap-16">
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
