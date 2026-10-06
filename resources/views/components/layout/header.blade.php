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
	 * All five nav items are always shown.
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
		// *Software* holds its place until its page is built (2026-10-06): no
		// route yet, so `#` and nothing to light it.
		['label' => 'Software', 'href' => '#', 'match' => []],
		// *Firmenschulung* joined on Marcel's call (2026-10-06), against the
		// mockup review's marker 8, which kept it out of the menu.
		['label' => 'Firmenschulung', 'href' => \App\Support\SiteUrl::training(), 'match' => "{$locale}.training"],
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
	<div class="grid min-h-[inherit] grid-cols-12 gap-x-16 sm:border-b sm:border-black lg:gap-x-40">
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

			**From md, not sm** (2026-10-06). Five links need 351px of text at sm's
			18px, and between 640 and ~760px the bar has about 354px for the links
			*and* the icons: the logo is a fixed 199px and gives nothing back. So
			the burger and its panel cover that band too, and the bar starts at
			768px. The `sm:` classes on this column are left as they were.
		--}}
		<div class="col-span-12 hidden sm:col-span-8 md:block">
			<nav class="grid grid-cols-12 gap-x-16 lg:gap-x-40" aria-label="Hauptnavigation">
				{{-- `menu/_site.scss`: bold, 24px, 16px at sm, 20px at lg. Legacy's
				     three links fill a `span-6` — on production Kurse begins at the
				     column's left edge and Kontakt ends exactly at its right.

				     **Five do not fit in it** (Software and Firmenschulung joined, 2026-10-06):
				     the list overflowed and *Über uns* broke onto two lines. So the links
				     take `span-10` (`span-9` from lg), the icons, which need little, the
				     rest, and each link stays on one line. `gap-x-6` is only a floor:
				     `justify-between` still spreads them when there is room, and 6px is
				     what lets them fit at 768 and 1024. --}}
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
					<li class="flex items-center" x-cloak x-show="$store.basket.count > 0">
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
	</div>

	{{--
		`menu/_site.scss` under `bp-xs` (here up to md, see the bar above): a teal panel behind an 8px white frame
		that stops short of the bottom, links right-aligned over black rules, and
		a white bar carrying the social links and the close control.

		`Profil` joins the list here — on mobile legacy shows it as a word rather
		than the icon it uses on desktop.
	--}}
	<div x-show="open" x-cloak
		class="fixed inset-0 z-[201] flex flex-col border-8 border-b-0 border-white bg-teal md:hidden"
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
			<ul class="mt-72">
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
		class="fixed right-20 bottom-20 z-[99] h-24 w-32 md:hidden"
		@click="toggle()" :aria-expanded="open" aria-label="Menü">
		<x-icon.burger class="w-full!" />
	</button>

</header>
