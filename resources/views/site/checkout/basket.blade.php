<x-layout.site title="Mein Warenkorb" auth>
	{{--
		Step 1 of 4, from `frontend/checkout/views/Overview.vue` and
		`shared/components/ui/layout/StackedList*.vue` ([[09-public-site]]).

		**The page is teal-gutted like the login**, because legacy's
		`checkout/index.blade.php` sets `is-auth` on `<html>` and every step
		after this one does the same.

		The heading sits in a bare `<aside>` rather than in `<x-layout.article>`:
		legacy's checkout puts `article.content-text` on the page with **only**
		an aside in it and the list as a sibling, so the list runs the full
		container width instead of a `span-8` column. `xs:hide` because the
		header's own title row already says *Mein Warenkorb* on a phone.
	--}}
	<article class="relative">
		<aside>
			<h1 class="hidden font-bold text-teal sm:block">Mein Warenkorb</h1>
		</aside>
	</article>

	{{-- `.stacked-list-container`: 48px above, 64 from `lg`. --}}
	<div class="mt-48 lg:mt-64" x-data="basketList">

		{{-- Nothing is asserted about the basket until the server has answered.
		     Claiming *leer* for the moment between paint and response is the
		     one thing this page must not do — it is the screen a customer
		     reaches by clicking *Warenkorb* in the confirmation. --}}
		<template x-if="settled && ! items.length">
			{{-- `.checkout-basket-empty`: 12px above, 24 from `sm`, 36 from `lg`. --}}
			<div class="mt-12 sm:mt-24 lg:mt-36">Dein Warenkorb ist leer...</div>
		</template>

		<template x-if="items.length">
			<div>
				{{--
					`.stacked-list-header` — a 12-column grid from `sm` with the
					**step on the left** in `span-4` and the title on the right
					in `span-8`. That is the order `StackedListHeader.vue`
					renders them in, whatever order the caller passes the slots,
					and it matches the page's own left-column rhythm.

					16px from `sm`, 18 from `lg`; 8px under each cell.

					`.stacked-list-header` sets a size and no line height, so
					production inherits the body's 1.3 and measures 23.4px on
					18px type. This used to have to say so: `lg:text-xl` brought
					Tailwind's own 1.4 with it and made the header 25.2, two
					pixels that pushed every row below it down. Since
					2026-09-22 a size is only a size
					(`resources/css/README.md`). The row underneath is a
					different case — `.stacked-list` states 1.4 itself, which is
					why *that* one is spelled out.
				--}}
				<header class="sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
					<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 1/4</strong></div>
					<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Übersicht</h2></div>
				</header>

				<template x-for="item in items" :key="item.event.uuid">
					<x-row.basket removable />
				</template>

				{{--
					`.stacked-list-footer`: a 2px `#969696` rule, 32px above and
					48 from `sm`, and a 12-column grid the link fills.

					`btn-next-wide` pushes the label and the arrow to the two
					edges — 16px type, 18 from `sm`, 24 from `lg`, 12px of
					vertical padding and 16 from `sm`, at line height 1.
				--}}
				<footer class="mt-32 grid grid-cols-12 gap-16 border-t-2 border-gray-400 sm:mt-48 lg:gap-40">
					<a href="{{ \App\Support\SiteUrl::checkout('address') }}"
						class="col-span-12 flex w-full items-center justify-between py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
						<span class="mr-8 sm:mr-12">Weiter</span>
						<x-icon.arrow-right />
					</a>
				</footer>
			</div>
		</template>

		{{-- The price came back an error — an expired code, most often. Legacy
		     never showed one here, because `Discount::apply()` turned a failure
		     into a zero ([[PriceBasket]]). --}}
		<template x-if="basket.error">
			<div class="mt-24 font-bold text-danger" x-text="basket.error"></div>
		</template>
	</div>

	{{-- *Entfernen* raises the same toast it does on the course page, and the
	     rental question has nowhere to fire here — but the pair is one
	     component and costs nothing hidden. --}}
	<x-dialog.basket />
</x-layout.site>
