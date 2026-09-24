@props(['event', 'bookmarked' => false, 'booked' => false])

@php
	$locale = app()->getLocale();

	/*
	 * `EventDate.time_start` is a `TIME` column, so it arrives as `08:30:00`.
	 * Legacy's accessor prints `date('H.i')` and the page says `08.30 – 17.00
	 * Uhr`; the dot is the separator on every date on the site.
	 *
	 * Formatted here rather than on the model: `Event::dateRange()` lives there
	 * because it is frozen into an invoice line and has to keep saying what it
	 * said. This is only ever read off a screen.
	 */
	$time = fn (?string $value) => $value === null
		? null
		: str_replace(':', '.', substr($value, 0, 5));

	$full = $event->isFull();
	$expert = $event->experts->first();

	/*
	 * Printed twice — in the third column, and again inside the second one for
	 * the phone. See the comment beside the mobile copy; the string is decided
	 * here so the two cannot drift.
	 */
	$fee = $event->free_of_charge ? 'kostenlos' : $event->fee();
@endphp

{{--
	`components/lists/_stacked.scss` (`%stacked-list` and `.stacked-list-event`)
	with `web/pages/events/components/card.blade.php`, rebuilt 1:1.

	One row of the **Aktuelle Kurse** list: a 12-column grid of three `span-4`
	columns — the dates behind a bookmark heart, then where and with whom, then
	the fee and the button. Measured against production on 2026-09-21: 32px
	above the row, a 1px black rule, 16px under it, and 1.4 line height on 18px
	type.

	Below `sm` the three columns stack and **the heart leaves the flow** — it is
	`position: absolute` in the top right of the row, where there is no column
	to put it in.

	**Every line of small print now reads at the row's own size** — the
	registration deadline, the state, and *Kurs ist ausgebucht* — where legacy
	drops all three to `.text-xsmall`. See the comments beside them.

	Which leaves no `text-*` class in this column at all, and that is the point:
	naming a Tailwind size brings its own line height with it, and
	`leading-[inherit]` is not the way out — that inherits the parent's
	**25.2px**, taller on 16px text than the 1.4 it came from
	(`resources/css/README.md`). Inheriting means saying nothing.
--}}
<article {{ $attributes->class([
	'relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl',
]) }}>
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- When and, beside it, the bookmark --}}
		<div class="sm:col-span-4">
			<div class="sm:flex">
				<div class="absolute top-12 right-0 sm:static sm:pr-16 sm:pt-4">
					@auth
						{{-- `Bookmark.vue`, as Alpine. Legacy toasts on both
						     verbs; the toast arrives with the basket, which is
						     where the component that draws it is going. --}}
						<button type="button"
							x-data="bookmark({ event: @js($event->uuid), saved: @js($bookmarked) })"
							@click="toggle()"
							:title="saved ? 'Von Merkliste entfernen' : 'Zur Merkliste hinzufügen'"
							:aria-pressed="saved"
							class="block transition-colors hover:text-teal">
							<span x-show="saved"><x-icon.heart active /></span>
							<span x-show="! saved"><x-icon.heart /></span>
						</button>
					@endauth

					@guest
						{{-- Legacy opens a dialog that says the Merkliste is for
						     registered users and offers a Login button. We have
						     no dialog component, and the link says the same
						     thing in one step. --}}
						<a href="{{ route('login') }}" title="Zur Merkliste hinzufügen"
							class="block transition-colors hover:text-teal">
							<x-icon.heart />
						</a>
					@endguest
				</div>

				{{--
					**One block for all the days, broken with `<br>`** — legacy's
					shape, and it is load-bearing. A `<div>` per day rounds each
					one's 2 × 25.2px up on its own, which made a two-day row a
					pixel taller than production and moved everything below it.

					**On a phone the day and its hours share a line**, as they do
					in the basket (Marcel, 2026-09-22). Legacy breaks them at
					every width, which on a three-day course spends six lines on
					what is really three facts. The break is hidden below `sm`
					and a comma stands in; a `<br>` set to `display: none`
					genuinely stops breaking.
				--}}
				<div>
					@foreach ($event->dates as $date)
						<strong class="font-bold">{{ $date->date->translatedFormat('d. F Y') }}</strong><span class="sm:hidden">,</span><br class="max-sm:hidden">
						{{ $time($date->time_start) }} – {{ $time($date->time_end) }} Uhr
						@if (! $loop->last)<br>@endif
					@endforeach
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

			@if ($expert)
				{{-- Legacy links the name to an expert page. There is none yet —
				     it arrives with chunk 04 ([[04-content]]) — and an anchor to
				     `#` inside a sentence is worse than none. Unlinked it looks
				     identical: the stacked list gives its links no underline and
				     only a teal hover. --}}
				<div>mit {{ $expert->first_name }} {{ $expert->last_name }}</div>
			@endif

			{{--
				**These read at the row's own size**, not a step down (Marcel,
				2026-09-22). Legacy gives them `.text-xsmall` — 12/14/16 against
				the row's 16/16/18 — so on the live site the deadline and the
				state are smaller than the expert's name directly above them,
				for no reason either the markup or the design gives. They are
				not a footnote; the state line is the one thing on the row that
				says whether the course is actually happening.

				So: `italic` and nothing else. No size and no `leading-*`, which
				is the only way to *inherit* here — naming a Tailwind size would
				drag its own line height in with it
				(`resources/css/README.md`).

				A departure from production, and a deliberate one.
			--}}
			{{--
				**On a phone the price comes before the two remarks** (Marcel,
				2026-09-22). Stacked, legacy's column order reads *deadline,
				state, price* — the two asides first and the number they are
				about last. Reversed, the facts close on what it costs and the
				remarks become the footnote they are.

				Printed here rather than moved, because `order` cannot reach
				across parents and the price lives in the **third** column. The
				alternatives were splitting the grid into five items with
				explicit `col-start`/`row-start`, or flattening the row — both
				of which would put the measured desktop layout at risk to fix a
				phone. A hidden copy changes nothing above `sm`: `$fee` is
				decided once, at the top.
			--}}
			<div class="sm:hidden">{{ $fee }}</div>

			{{-- The breath Marcel asked for, between the number and the two
			     italics. Phone only — at `sm` these are back under the expert
			     in their own column and there is nothing above them to be
			     spaced from. --}}
			<div class="max-sm:mt-16">
				@if ($event->registration_until && ! $booked && ! $full)
					<div>
						<em class="italic">
							Anmeldung möglich bis {{ $event->registration_until->format('d.m.Y') }}
						</em>
					</div>
				@endif

				@if ($event->state === \App\Enums\EventState::Confirmed)
					<div class="text-success"><em class="italic">Kurs findet statt</em></div>
				@else
					<div class="text-warning"><em class="italic">Kurs offen, wird bestätigt</em></div>
				@endif
			</div>
		</div>

		{{-- What it costs, and the way in --}}
		<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
			{{-- Hidden on a phone, where the copy in the second column stands
			     in for it — see there. --}}
			<div class="max-sm:hidden sm:mr-32 lg:mr-48">{{ $fee }}</div>

			<div class="mt-24 sm:mt-0">
				@if ($booked)
					{{-- The seat's own screen in the portal, which is where
					     *Verwalten* has always meant to go — it pointed at
					     `/dashboard` while there was nothing better
					     ([[09-public-site]]). --}}
					<x-ui.button variant="outline"
						href="{{ \App\Support\SiteUrl::studentEvent($event->uuid) }}"
						title="Buchung verwalten">
						Verwalten
					</x-ui.button>
				@elseif ($full)
					{{-- At the row's size, like the deadline and the state line
					     (Marcel, 2026-09-22). It stands in for the button
					     rather than sitting in a sentence, which is why it was
					     left behind the first time — but it is the reason there
					     is no button, so it is the last thing on the row that
					     should be whispering. --}}
					<div class="pl-16 text-right text-danger italic">
						Kurs ist ausgebucht
					</div>
				@elseif (auth()->check() && ! auth()->user()->hasVerifiedEmail())
					{{-- Legacy sends an unverified account to the verification
					     screen rather than into the basket, because the
					     confirmation mail has nowhere to land. --}}
					<x-ui.button href="{{ route('verification.notice') }}">Buchen</x-ui.button>
				@else
					{{-- `Basket.vue`: *Buchen* while the event is not in the
					     basket, the grey *Entfernen* once it is.

					     **`book()` is not always an add.** Legacy renders two
					     different *Buchen* buttons off `hasRentals` — one that
					     adds, and one that first asks whether to rent a laptop
					     at CHF 80 excl. VAT. The question has to come first,
					     because the rental is frozen onto the booking with its
					     price ([[PriceBasket]]) — and it is asked **only while a
					     laptop is left**, legacy's `has_rentals_available`: the
					     room's count less those already rented
					     ([[Event::rentalsLeft]]). The dialog itself is
					     `<x-dialog.basket />`, once per page. --}}
					<div x-data="{ uuid: @js($event->uuid), rentals: @js($event->rentalsLeft() > 0) }">
						{{-- *Buchen* renders without `x-cloak`, so it is what a
						     visitor with no JavaScript is left holding, and the
						     common case never flashes. Only *Entfernen* has to
						     wait for the store to be read. --}}
						<x-ui.button x-show="! $store.basket.has(uuid)"
							@click="$store.basket.book(uuid, rentals)">Buchen</x-ui.button>

						<x-ui.button variant="secondary" x-cloak x-show="$store.basket.has(uuid)"
							@click="$store.basket.remove(uuid)">Entfernen</x-ui.button>
					</div>
				@endif
			</div>
		</div>
	</div>
</article>
