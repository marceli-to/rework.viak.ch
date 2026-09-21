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

	**The small print restates the row's line height**, 1.5 and 1.4 from `sm`.
	Legacy's `.text-xsmall` sets a size and nothing else, so those lines simply
	inherit — and a `text-*` class here does not, it brings Tailwind's own
	pairing with it (`resources/css/README.md`). `leading-[inherit]` is not the
	way out: it inherits the parent's line height as **25.2px**, which on 16px
	text is taller again than the 1.4 it came from. The row measured 119px
	against production's 118 either way until the numbers were written down.
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

				{{-- **One block for all the days, broken with `<br>`** — legacy's
				     shape, and it is load-bearing. A `<div>` per day rounds each
				     one's 2 × 25.2px up on its own, which made a two-day row a
				     pixel taller than production and moved everything below it. --}}
				<div>
					@foreach ($event->dates as $date)
						<strong class="font-bold">{{ $date->date->translatedFormat('d. F Y') }}</strong><br>
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

			@if ($event->registration_until && ! $booked && ! $full)
				<div>
					<em class="text-xs leading-[1.5] italic sm:text-md sm:leading-[1.4] lg:text-lg">
						Anmeldung möglich bis {{ $event->registration_until->format('d.m.Y') }}
					</em>
				</div>
			@endif

			@if ($event->state === \App\Enums\EventState::Confirmed)
				<div class="text-success">
					<em class="text-xs leading-[1.5] italic sm:text-md sm:leading-[1.4] lg:text-lg">Kurs findet statt</em>
				</div>
			@else
				<div class="text-warning">
					<em class="text-xs leading-[1.5] italic sm:text-md sm:leading-[1.4] lg:text-lg">Kurs offen, wird bestätigt</em>
				</div>
			@endif
		</div>

		{{-- What it costs, and the way in --}}
		<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
			<div class="sm:mr-32 lg:mr-48">
				@if ($event->free_of_charge)
					kostenlos
				@else
					{{ $event->fee() }}
				@endif
			</div>

			<div class="mt-24 sm:mt-0">
				@if ($booked)
					<x-site.button variant="outline" href="{{ route('dashboard') }}" title="Buchung verwalten">
						Verwalten
					</x-site.button>
				@elseif ($full)
					<div class="pl-16 text-right text-sm leading-[1.5] text-danger italic sm:text-md sm:leading-[1.4] lg:text-lg">
						Kurs ist ausgebucht
					</div>
				@elseif (auth()->check() && ! auth()->user()->hasVerifiedEmail())
					{{-- Legacy sends an unverified account to the verification
					     screen rather than into the basket, because the
					     confirmation mail has nowhere to land. --}}
					<x-site.button href="{{ route('verification.notice') }}">Buchen</x-site.button>
				@else
					{{-- `Basket.vue`: *Buchen* while the event is not in the
					     basket, the grey *Entfernen* once it is.

					     **The rental dialog is still owed.** Legacy asks, before
					     adding an event with `rentals_available`, whether to
					     rent a laptop at CHF 80 excl. VAT — and says in the same
					     breath that you can change it later. Adding with no
					     rental is that dialog's cheaper answer, so nobody is
					     charged for something they did not ask for; what is
					     missing is the offer. It needs the modal the basket page
					     needs too, and arrives with it ([[09-public-site]]). --}}
					<div x-data="{ uuid: @js($event->uuid) }">
						{{-- *Buchen* renders without `x-cloak`, so it is what a
						     visitor with no JavaScript is left holding, and the
						     common case never flashes. Only *Entfernen* has to
						     wait for the store to be read. --}}
						<x-site.button x-show="! $store.basket.has(uuid)"
							@click="$store.basket.add(uuid)">Buchen</x-site.button>

						<x-site.button variant="secondary" x-cloak x-show="$store.basket.has(uuid)"
							@click="$store.basket.remove(uuid)">Entfernen</x-site.button>
					</div>
				@endif
			</div>
		</div>
	</div>
</article>
