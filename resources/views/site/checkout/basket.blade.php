<x-layout.site title="Mein Warenkorb" auth>
	{{--
		Step 1 of 4, from `frontend/checkout/views/Overview.vue` and
		`shared/components/ui/layout/StackedList*.vue` ([[09-public-site]]).

		**The page is teal-gutted like the login**, because legacy's
		`checkout/index.blade.php` sets `is-auth` on `<html>` and every step
		after this one does the same.

		The heading sits in a bare `<aside>` rather than in `<x-site.article>`:
		legacy's checkout puts `article.content-text` on the page with **only**
		an aside in it and the list as a sibling, so the list runs the full
		container width instead of a `span-8` column. `xs:hide` because the
		header's own title row already says *Mein Warenkorb* on a phone.
	--}}
	<article class="relative">
		<aside>
			<h1 class="hidden leading-[1.3] font-bold text-teal sm:block">Mein Warenkorb</h1>
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

					**`leading-[1.3]` has to be said.** `.stacked-list-header`
					sets a size and no line height, so production inherits the
					body's 1.3 and measures 23.4px on 18px type. Saying
					`lg:text-xl` brings Tailwind's own 1.4 with it and made the
					header 25.2 — two pixels that push every row below it down
					(`resources/css/README.md`). The row underneath is *not*
					this: `.stacked-list` states 1.4 itself.
				--}}
				<header class="leading-[1.3] sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
					<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 1/4</strong></div>
					<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Übersicht</h2></div>
				</header>

				<template x-for="item in items" :key="item.event.uuid">
					{{--
						`.stacked-list-event` — the same row the course page
						uses, measured there against production: 16/8 above and
						inside, 32/16 from `sm`, a 1px black rule, 1.5 then 1.4
						line height on 16/18px type.

						**Red when the course is already booked.** `has-booking`
						recolours the rule and every text inside it, which is
						what `*:not([class*=btn-])` does — the buttons keep
						their own colours. `CompleteCheckout` refuses this line
						outright, so the warning is the customer meeting the
						refusal early rather than at the last step.
					--}}
					<article class="relative mt-16 border-t pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl"
						:class="item.booked
							? 'border-danger text-danger [&_a:not([class*=bg-])]:text-danger'
							: 'border-black'">

						<template x-if="item.booked">
							<div class="font-bold">Du hast bereits eine Buchung für diesen Kurs!</div>
						</template>

						<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
							{{-- What, and when. The basket row leads with the
							     course title, which the course page's own row
							     has no need of. --}}
							<div class="sm:col-span-4">
								<h2 class="font-bold">
									<a :href="`/{{ app()->getLocale() }}/{{ \App\Support\SiteUrl::segment('course') }}/${item.event.course.slug.de}`"
										:title="item.event.course.title.de"
										x-text="item.event.course.title.de"
										class="hover:text-teal"></a>
								</h2>

								{{-- One block for all the days, broken with
								     `<br>` — a `<div>` per day rounds each
								     one's line box separately and makes a
								     two-day row a pixel taller. --}}
								<div>
									<template x-for="(date, i) in item.event.dates" :key="i">
										<span>
											<strong class="font-bold" x-text="dateLong(date.date)"></strong><br>
											<span x-text="`${time(date.time_start)} – ${time(date.time_end)} Uhr`"></span>
											<template x-if="i < item.event.dates.length - 1"><br></template>
										</span>
									</template>
								</div>
							</div>

							{{-- Where, and with whom. **No state line** — the
							     basket passes `is_basket`, which is the one
							     thing it takes away from the row. --}}
							<div class="sm:col-span-4">
								<div x-show="item.event.online">Onlinekurs</div>
								<div x-show="! item.event.online" x-text="location(item.event)"></div>
								<div x-show="experts(item.event)" x-text="`mit ${experts(item.event)}`"></div>
							</div>

							{{-- What it costs, and the way out. --}}
							<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
								<div class="sm:mr-32 lg:mr-48">
									<span x-show="item.event.free_of_charge">kostenlos</span>
									<span x-show="! item.event.free_of_charge" x-text="item.course_fee"></span>
								</div>

								<div class="mt-24 sm:mt-0">
									<x-site.button variant="secondary" class="w-auto"
										@click="$store.basket.remove(item.event.uuid)">Entfernen</x-site.button>
								</div>
							</div>

							{{--
								The laptop is **its own row in the same grid**,
								not a line inside the course's. Legacy appends
								three more `.stacked-list__col` divs after the
								first three, so they wrap onto a second row of
								the same twelve columns — which is why the
								price lines up under the course's.
							--}}
							<template x-if="item.rental">
								<div class="sm:col-span-4"><strong class="font-bold">Mietcomputer</strong></div>
							</template>
							<template x-if="item.rental">
								<div class="sm:col-span-4">&nbsp;</div>
							</template>
							<template x-if="item.rental">
								<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
									<div class="sm:mr-32 lg:mr-48">
										{{-- `CHF 80.00` — legacy writes the
										     currency here and nowhere else in
										     this list, which is its own
										     inconsistency and not ours. --}}
										<span x-text="`CHF ${item.rental_fee}`"></span>
									</div>
									<div class="mt-24 sm:mt-0">
										<x-site.button variant="secondary" class="w-auto"
											@click="$store.basket.setRental(item.event.uuid, false)">Entfernen</x-site.button>
									</div>
								</div>
							</template>
						</div>
					</article>
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
	<x-site.basket-dialogs />
</x-layout.site>
