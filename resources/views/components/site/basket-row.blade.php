@props(['removable' => false])

{{--
	One line of a priced basket, drawn in the browser ([[09-public-site]]).

	`.stacked-list-event` — the same row the course page uses, measured there
	against production: 16/8 above and inside, 32/16 from `sm`, a 1px black
	rule, 1.5 then 1.4 line height on 16/18px type.

	Used by **step 1 and step 4**, which is why it is a component: legacy renders
	the same `StackedListEvent` on both, with `is_basket` set and the `action`
	slot filled only on the basket. `removable` is that slot.

	Expects `item` in scope — a `BasketResource` line — and the `basketList`
	helpers for the dates and the place. Nothing here computes a price.
--}}
<article class="relative mt-16 border-t pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl"
	:class="item.booked
		? 'border-danger text-danger [&_a:not([class*=bg-])]:text-danger'
		: 'border-black'">

	{{-- **Red when the course is already booked.** `has-booking` recolours the
	     rule and every text inside it, which is what `*:not([class*=btn-])`
	     does — the buttons keep their own colours. `CompleteCheckout` refuses
	     this line outright, so the warning is the customer meeting the refusal
	     early rather than at the last step. --}}
	<template x-if="item.booked">
		<div class="font-bold">Du hast bereits eine Buchung für diesen Kurs!</div>
	</template>

	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- What, and when. The basket row leads with the course title, which
		     the course page's own row has no need of. --}}
		<div class="sm:col-span-4">
			<h2 class="font-bold">
				<a :href="`/{{ app()->getLocale() }}/{{ \App\Support\SiteUrl::segment('course') }}/${item.event.course.slug.de}`"
					:title="item.event.course.title.de"
					x-text="item.event.course.title.de"
					class="hover:text-teal"></a>
			</h2>

			{{-- One block for all the days, broken with `<br>` — a `<div>` per
			     day rounds each one's line box separately and makes a two-day
			     row a pixel taller. --}}
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

		{{-- Where, and with whom. **No state line** — the basket passes
		     `is_basket`, which is the one thing it takes away from the row. --}}
		<div class="sm:col-span-4">
			<div x-show="item.event.online">Onlinekurs</div>
			<div x-show="! item.event.online" x-text="location(item.event)"></div>
			<div x-show="experts(item.event)" x-text="`mit ${experts(item.event)}`"></div>
		</div>

		{{--
			What it costs, and the way out.

			**Every amount is `CHF n.nn` and right-aligned** (Marcel,
			2026-09-22). Legacy writes the currency on the laptop line and not
			on the course line directly above it, in the same column of the same
			row — an inconsistency this doc used to carry across on parity
			grounds and no longer does, because the two sit a line apart and
			read as two different kinds of number.

			Right-aligned for the reason money columns usually are: the decimal
			points line up, so the eye can add them. It also puts the row fees
			in the **same column as the totals** on step 4, where there is no
			*Entfernen* to sit beside and the rest of the column is `text-right`
			already.

			`grow` is what makes that work — the box has to fill the column
			before aligning inside it. The gap to the button is legacy's
			`mr-8x`/`md:mr-12x`, so it only applies where there is a button.
		--}}
		<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
			<div @class(['grow sm:text-right', 'sm:mr-32 lg:mr-48' => $removable])>
				<span x-show="item.event.free_of_charge">kostenlos</span>
				<span x-show="! item.event.free_of_charge" x-text="`CHF ${item.course_fee}`"></span>
			</div>

			@if ($removable)
				<div class="mt-24 sm:mt-0">
					<x-site.button variant="secondary"
						@click="$store.basket.remove(item.event.uuid)">Entfernen</x-site.button>
				</div>
			@endif
		</div>

		{{--
			The laptop is **its own row in the same grid**, not a line inside
			the course's. Legacy appends three more `.stacked-list__col` divs
			after the first three, so they wrap onto a second row of the same
			twelve columns — which is why CHF 80.00 lines up under the course
			fee.
		--}}
		{{-- 24px above it **below `sm` only**, where the twelve columns have
		     collapsed into a stack and this heading would otherwise sit flat
		     against the *Entfernen* of the course above it — making the button
		     read as the laptop's. Same 24 the action itself uses.

		     Legacy has no margin here, and its phone rendering of this row
		     cannot be checked: the basket is behind a login on production. A
		     judgement call, said out loud rather than passed off as
		     measured. --}}
		<template x-if="item.rental">
			<div class="mt-24 sm:col-span-4 sm:mt-0"><strong class="font-bold">Mietcomputer</strong></div>
		</template>
		{{-- Legacy's middle column on the laptop row is a literal `&nbsp;`,
		     there to occupy a grid cell. **Below `sm` there is no grid**, so it
		     renders as a blank 24px line between *Mietcomputer* and its price —
		     measured on a 500px window, 2026-09-22. A spacer for a grid that is
		     not there has nothing to space. --}}
		<template x-if="item.rental">
			<div class="hidden sm:col-span-4 sm:block">&nbsp;</div>
		</template>
		<template x-if="item.rental">
			<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
				<div @class(['grow sm:text-right', 'sm:mr-32 lg:mr-48' => $removable])>
					<span x-text="`CHF ${item.rental_fee}`"></span>
				</div>

				@if ($removable)
					<div class="mt-24 sm:mt-0">
						<x-site.button variant="secondary"
							@click="$store.basket.setRental(item.event.uuid, false)">Entfernen</x-site.button>
					</div>
				@endif
			</div>
		</template>
	</div>
</article>
