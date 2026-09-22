@props([
	'event',
	'booking' => null,
	'icon' => null,
	'action' => null,
	'rentalAction' => null,
	'rentalPrompt' => null,
	'showExperts' => true,
	'showFee' => true,
	'marked' => false,
])

@php
	$locale = app()->getLocale();

	// `08:30:00` off a `TIME` column; the site writes `08.30 – 17.00 Uhr`, and
	// the dot is the separator on every date on the site. Same helper as
	// `x-site.event-card`, and for the same reason it is not on the model:
	// `Event::dateRange()` is frozen into invoice lines, this is only ever read
	// off a screen.
	$time = fn (?string $value) => $value === null
		? null
		: str_replace(':', '.', substr($value, 0, 5));

	/*
	 * What this seat costs *this* student — the event fee less whatever code
	 * they used, which is legacy's `event.fee - booking.discount_amount`.
	 *
	 * It is the **offer**, not the money: six historical bookings were invoiced
	 * at half their `course_fee` under an arrangement recorded only on the
	 * invoice, and the invoice is what was charged ([[Booking]]). The number
	 * here is what the portal has always shown.
	 */
	$fee = match (true) {
		$event->free_of_charge => 'kostenlos',
		$booking !== null => number_format((float) $booking->netFee(), 2, '.', ''),
		default => $event->fee(),
	};

	$expert = $event->experts->first();
@endphp

{{--
	One row of the portal's course lists — `StackedListEvent.vue` in its public
	form, server-rendered ([[08-accounts]], [[09-public-site]]).

	`%stacked-list` geometry, measured against the live stylesheet on
	2026-09-22 and identical to the row the course page and the basket already
	draw: 16/8 above and inside, 32/16 from `sm`, a 1px black rule, 1.5 then 1.4
	line height on 16/18px type, and a twelve-column grid of three `span-4`
	columns with a 16px gap that becomes 40 from `lg`.

	**What separates it from `x-site.event-card`** is that this row leads with
	the course title — the portal lists seats across many courses, the course
	page lists dates within one — and that its buttons come from the caller
	rather than from the basket. Kept as a second component rather than folded
	into the first: the card's layout is measured against production and carries
	two deliberate departures from it, and widening it to cover four more
	callers would put that at risk for no gain.
--}}
<article @class([
	'relative mt-16 border-t pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl',
	// `has-booking`: the rule and every text in the row go red, while the
	// buttons keep their own colours — legacy's `*:not([class*=btn-])`.
	'border-danger text-danger [&_a:not([class*=bg-])]:text-danger' => $marked,
	'border-black' => ! $marked,
])>
	@if ($marked)
		<div class="font-bold">Du hast bereits eine Buchung für diesen Kurs!</div>
	@endif

	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- What, when, and the icon that belongs to the row --}}
		<div class="sm:col-span-4">
			<div @class(['sm:flex' => $icon !== null])>
				@if ($icon)
					{{-- `.stacked-list__icon`: 16px to its right and 4px down
					     from `sm`; **below it there is no column to sit in**, so
					     it leaves the flow for the row's top right corner —
					     measured at 12px down, hard against the right edge. --}}
					<div class="absolute top-12 right-0 sm:static sm:pr-16 sm:pt-4">{{ $icon }}</div>
				@endif

				<div>
					<h2 class="font-bold">
						<a href="{{ \App\Support\SiteUrl::course($event->course->getTranslation('slug', $locale)) }}"
							title="{{ $event->course->getTranslation('title', $locale) }}"
							class="hover:text-teal">{{ $event->course->getTranslation('title', $locale) }}</a>
					</h2>

					{{-- One block for all the days, broken with `<br>`: a
					     `<div>` per day rounds each line box separately and
					     makes a two-day row a pixel taller. On a phone the day
					     and its hours share a line, as they do on the course
					     page and in the basket (Marcel, 2026-09-22). --}}
					<div>
						@foreach ($event->dates as $date)
							<strong class="font-bold">{{ $date->date->translatedFormat('d. F Y') }}</strong><span class="sm:hidden">,</span><br class="max-sm:hidden">
							{{ $time($date->time_start) }} – {{ $time($date->time_end) }} Uhr
							@if (! $loop->last)<br>@endif
						@endforeach
					</div>
				</div>
			</div>
		</div>

		{{-- Where, with whom, and whether it is going ahead --}}
		<div class="sm:col-span-4">
			<div>
				@if ($event->online)
					Onlinekurs
				@elseif ($event->location?->map)
					<a href="{{ $event->location->map }}" target="_blank" rel="noopener"
						title="Karte anzeigen" class="hover:text-teal">
						{{ $event->location->getTranslation('description', $locale) }}
					</a>
				@else
					{{ $event->location?->getTranslation('description', $locale) }}
				@endif
			</div>

			@if ($showExperts && $expert)
				{{-- Unlinked, as on the course page: the expert page arrives
				     with chunk 04 ([[04-content]]) and an anchor to `#` inside
				     a sentence is worse than none. --}}
				<div>mit {{ $expert->first_name }} {{ $expert->last_name }}</div>
			@endif

			<x-site.event-state :event="$event" />
		</div>

		{{-- What it costs, and what can be done about it --}}
		<div @class([
			'sm:col-span-4',
			'sm:flex sm:items-start sm:justify-between' => $action !== null,
			'sm:flex sm:justify-end' => $action === null,
		])>
			@if ($showFee)
				{{-- The 32/48px gap is legacy's `mr-8x`/`md:mr-12x` and belongs
				     to the fee only where a button follows it. --}}
				<div @class(['sm:mr-32 lg:mr-48' => $action !== null])>{{ $fee }}</div>
			@endif

			@if ($action)
				{{-- `.stacked-list__action`: 24px above it on a phone, where the
				     columns have stacked and it would otherwise sit against the
				     state line; nothing from `sm`, where it is a flex item. --}}
				<div class="mt-24 sm:mt-0">{{ $action }}</div>
			@endif
		</div>

		{{--
			The laptop, as **its own row in the same grid**. Legacy appends
			three more `.stacked-list__col` divs after the first three so they
			wrap onto a second row of the same twelve columns, which is what
			lines CHF 80.00 up under the course fee — the same shape
			`x-site.basket-row` draws.
		--}}
		@if ($booking?->has_rental)
			{{-- 24px above it below `sm` only, where the grid has collapsed and
			     this heading would otherwise read as a label for the button of
			     the row above it. --}}
			<div class="mt-24 sm:col-span-4 sm:mt-0"><strong class="font-bold">Mietcomputer</strong></div>
			{{-- Legacy's middle cell is a literal `&nbsp;` holding a grid
			     position. Below `sm` there is no grid, so it renders as a blank
			     line — a spacer for a grid that is not there. --}}
			<div class="hidden sm:col-span-4 sm:block">&nbsp;</div>
			<div @class([
				'sm:col-span-4',
				'sm:flex sm:items-start sm:justify-between' => $rentalAction !== null,
				'sm:flex sm:justify-end' => $rentalAction === null,
			])>
				<div @class(['sm:mr-32 lg:mr-48' => $rentalAction !== null])>{{ number_format((float) $booking->rental_fee, 2, '.', '') }}</div>
				@if ($rentalAction)
					<div class="mt-24 sm:mt-0">{{ $rentalAction }}</div>
				@endif
			</div>
		@endif

		{{--
			*Falls Du keinen Laptop hast …* — the offer to rent one, which
			legacy puts in a `span-12` under the row rather than in a column,
			because it is a sentence and a button rather than a fact.
		--}}
		@if ($rentalPrompt)
			{{-- 24px above it **below `sm` only**, like the laptop heading: from
			     `sm` the grid's own 16/40px row gap already separates it, and a
			     margin on top of that would double the space. Legacy's grid
			     carries `row-gap` as well as `column-gap` — measured at 40px on
			     production, 2026-09-22 — which is easy to miss from the SCSS,
			     where the two come from one `gap`. --}}
			<div class="mt-24 sm:col-span-12 sm:mt-0">{{ $rentalPrompt }}</div>
		@endif
	</div>
</article>
