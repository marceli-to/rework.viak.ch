<x-layout.site title="Profil" heading="Mein Profil" auth>
	@php($locale = app()->getLocale())

	{{--
		*Mein Profil* — `backend/student/views/Index.vue`, server-rendered
		([[08-accounts]], [[09-public-site]]).

		**Teal-gutted**, because legacy's `web/pages/user/student/index.blade.php`
		sets `is-auth` on `<html>`, exactly as the checkout and the auth screens
		do.

		Four collapsibles under the address block, in two containers — which is
		legacy's grouping and not decoration: the three course lists belong
		together and *Dokumente* is its own subject.

		**The form is not here.** Legacy toggles it in place off `isEdit`; it has
		a screen of its own at `/de/student/profil/bearbeiten`, because that
		state is a component's and does not survive leaving the page — which
		*Rechnungsadressen*, living inside the form with links to screens of
		their own, does every time somebody adds an address
		([[SiteUrl::studentProfileEdit]]).

		Which leaves this screen with no JavaScript of its own: the pencil is a
		link. What Alpine is left on the page belongs to the collapsibles, the
		basket and the cancellation dialogs.
	--}}
	<x-layout.article>
		{{-- `.icon-edit` is `position: absolute; top: 0; right: 0` at 18×18, and
		     `article.content-text` is the `position: relative` it hangs off —
		     measured 2026-09-22. It sits outside `.text__body`, so it is over
		     the aside rather than over the column.

		     A link where legacy has an `<a href="">` that toggles. --}}
		<a href="{{ \App\Support\SiteUrl::studentProfileEdit() }}"
			class="absolute top-0 right-0 block transition-colors hover:text-teal"
			title="Profil bearbeiten">
			<x-icon.edit class="w-18" />
		</a>

		<x-slot:aside>
			{{-- Hidden below `sm` — legacy's `xs:hide` — because the header
			     already carries the page title on a phone. --}}
			<h1 class="hidden font-bold text-teal sm:block">Mein Profil</h1>

			<x-ui.back-link :href="route('logout')" label="Logout" direction="right" method="post" />
		</x-slot:aside>

		@if (session('status'))
			<x-ui.toast variant="success">{{ session('status') }}</x-ui.toast>
		@endif

		{{-- The address as a block, then the email. Legacy renders it into a
		     `<pre>` from a server-built string; the lines are elements here, so
		     they can be styled and read out. --}}
		<div>
			@if ($user->company){{ $user->company }}<br>@endif
			{{ $user->name }}<br>
			{{ $user->street }} {{ $user->street_no }}<br>
			{{ $user->zip }} {{ $user->city }}
			@if ($user->country && $user->country_code !== 'ch')<br>{{ $user->country->name }}@endif
		</div>
		<div><a href="mailto:{{ $user->email }}" class="hover:text-teal">{{ $user->email }}</a></div>
	</x-layout.article>

	{{-- `.stacked-list-container` / `.collapsible-container`: 48px above, 64
	     from `lg`. The 64px under each collapsible collapses against the next
	     container's 48, so the gap between the two groups is 64 rather than
	     112 — legacy's arithmetic, kept by keeping its markup. --}}
	<div class="mt-48 lg:mt-64">
		<x-ui.collapsible title="Merkliste" :expanded="false" :count="$bookmarks->count()">
			@forelse ($bookmarks as $event)
				{{-- The heart and the basket buttons, which is what a bookmark
				     row is for. `x-card.event` draws the same pair on the
				     course page and its Alpine components are the same ones.

				     **`hideAfter` and the `x-data` on the row**, not on the
				     heart: this is the list *of* bookmarks, so un-hearting one
				     has to take its row away — legacy passes the same prop here
				     and nowhere else ([[bookmark]]). Without it the row stayed,
				     advertising a course that was no longer on the list. --}}
				{{-- `Js::from`, because **a Blade directive inside a component
				     tag's attribute is not compiled** — `@js(…)` reaches the
				     browser as those six characters and Alpine never
				     initialises, silently. An echo is compiled, and `Js` is
				     `Htmlable` so `{{ }}` does not escape it twice. The heart on
				     the course card gets away with `@js` because it sits on a
				     plain `<button>`. --}}
				<x-row.event :event="$event"
					x-data="bookmark({{ \Illuminate\Support\Js::from(['event' => $event->uuid, 'saved' => true, 'hideAfter' => true]) }})"
					x-show="! removed">
					<x-slot:icon>
						<button type="button"
							@click="toggle()"
							:title="saved ? 'Von Merkliste entfernen' : 'Zur Merkliste hinzufügen'"
							:aria-pressed="saved"
							class="block transition-colors hover:text-teal">
							<span x-show="saved"><x-icon.heart active /></span>
							<span x-show="! saved"><x-icon.heart /></span>
						</button>
					</x-slot:icon>

					<x-slot:action>
						@if ($event->isBookable())
							<div x-data="{ uuid: @js($event->uuid), rentals: @js((bool) $event->rentals_available) }">
								<x-ui.button x-show="! $store.basket.has(uuid)"
									@click="$store.basket.book(uuid, rentals)">Buchen</x-ui.button>
								<x-ui.button variant="secondary" x-cloak x-show="$store.basket.has(uuid)"
									@click="$store.basket.remove(uuid)">Entfernen</x-ui.button>
							</div>
						@endif
					</x-slot:action>
				</x-row.event>
			@empty
				<p class="mt-16 italic">Deine Merkliste ist leer.</p>
			@endforelse
		</x-ui.collapsible>

		<x-ui.collapsible title="Gebuchte Kurse" :expanded="true" :count="$upcoming->count()">
			@forelse ($upcoming as $booking)
				<x-row.event :event="$booking->event" :booking="$booking">
					{{-- **Teal**, which is the icon's own colour in legacy —
					     `Checkmark.vue` hardcodes `fill="#46baba"`. The Blade
					     set normalises every icon to `currentColor` so a
					     `text-*` can reach it (`resources/css/README.md`), which
					     is right and means the colour has to be said here
					     instead of being smuggled in with the artwork. --}}
					<x-slot:icon><x-icon.checkmark class="text-teal" /></x-slot:icon>

					<x-slot:action>
						<x-ui.button href="{{ \App\Support\SiteUrl::studentEvent($booking->event->uuid) }}"
							class="mb-8" title="Detail">Detail</x-ui.button>

						{{-- *Annullieren* asks first, and **what it asks
						     depends on the date**: inside the penalty window the
						     dialog names the amount and the rate before it lets
						     the student confirm. Both figures are the server's
						     ([[StudentPortalController::penalties]]). The
						     cancellation itself is
						     `PATCH /api/bookings/{booking}/cancel` — the same
						     [[CancelBooking]] the dashboard calls, penalty rule
						     and all. --}}
						{{-- `Js::from` rather than `@js`, and the difference is
						     not cosmetic: **a Blade directive inside a component
						     tag's attribute is not compiled**, so `@js(...)`
						     reaches the browser as those six characters and
						     Alpine answers *Invalid or unexpected token*. An
						     echo is, and `Js` is `Htmlable`, so `{{ }}` hands
						     over the JSON without escaping it twice. The
						     `x-data="bookmark({ … @js(…) })"` on the course card
						     works because it sits on a plain `<button>`. --}}
						<x-ui.button variant="secondary"
							@click="$store.portal.askCancel({{ \Illuminate\Support\Js::from([
								'uuid' => $booking->uuid,
								'penalty' => $penalties[$booking->uuid]['applies'],
								'amount' => $penalties[$booking->uuid]['amount'],
								'rate' => $penalties[$booking->uuid]['rate'],
							]) }})">
							Annullieren
						</x-ui.button>
					</x-slot:action>

					@if ($booking->has_rental && $booking->isEditable())
						<x-slot:rentalAction>
							<x-ui.button variant="secondary"
								@click="$store.portal.askCancelRental({{ \Illuminate\Support\Js::from(['uuid' => $booking->uuid]) }})">
								Annullieren
							</x-ui.button>
						</x-slot:rentalAction>
					@endif

					{{-- The offer to rent one, where the event has machines and
					     this booking has not taken one. Legacy shows it in a
					     `span-12` under the row, and the price it quotes is
					     hardcoded in the sentence — `Event::rental_fee` is the
					     one the booking would actually freeze
					     ([[PriceBasket]]), so the sentence reads it. --}}
					@if (! $booking->has_rental && $booking->event->rentals_available && $booking->isEditable())
						<x-slot:rentalPrompt>
							<div class="sm:flex sm:items-start sm:justify-between">
								<div class="sm:pr-40 lg:pr-80">
									Falls Du keinen Laptop hast, oder dieser den Anforderungen nicht
									genügt, kannst Du bei uns einen Computer mieten. Die Kosten dafür
									belaufen sich auf CHF {{ number_format((float) config('invoice.rental_fee'), 2, '.', '') }} (exkl. MwSt.)
								</div>
								{{-- `max-w-200px` is legacy's own cap on this one
								     button, and it is the only place on the site
								     that sets one. --}}
								<div class="mt-24 sm:mt-0 sm:max-w-200">
									<x-ui.button variant="secondary"
										@click="$store.portal.addRental({{ \Illuminate\Support\Js::from($booking->uuid) }})">
										Buchen
									</x-ui.button>
								</div>
							</div>
						</x-slot:rentalPrompt>
					@endif
				</x-row.event>
			@empty
				<p class="mt-16 italic">Du hast noch keine Kurse gebucht.</p>
			@endforelse
		</x-ui.collapsible>

		{{--
			*Absolvierte Kurse* — everything whose date has passed.

			**Not legacy's rule.** There the split is two spatie flags, and
			`isConcluded` is only ever set for a booking somebody already ticked
			as having attended — so 67 seats on courses that have already run
			are still listed as *Gebuchte Kurse* on the live site, across 63
			students, the oldest from March 2023, each with a live *Annullieren*
			beside it. The reasoning and the measurement are in
			[[StudentPortalController::splitBookings]].
		--}}
		<x-ui.collapsible title="Absolvierte Kurse" :expanded="false" :count="$past->count()">
			@forelse ($past as $booking)
				<x-row.event :event="$booking->event" :booking="$booking">
					{{-- **Teal**, which is the icon's own colour in legacy —
					     `Checkmark.vue` hardcodes `fill="#46baba"`. The Blade
					     set normalises every icon to `currentColor` so a
					     `text-*` can reach it (`resources/css/README.md`), which
					     is right and means the colour has to be said here
					     instead of being smuggled in with the artwork. --}}
					<x-slot:icon><x-icon.checkmark class="text-teal" /></x-slot:icon>
					<x-slot:action>
						<x-ui.button href="{{ \App\Support\SiteUrl::studentEvent($booking->event->uuid) }}"
							title="Detail">Detail</x-ui.button>
					</x-slot:action>
				</x-row.event>
			@empty
				<p class="mt-16 italic">Du hast noch keine Kurse absolviert.</p>
			@endforelse
		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		<x-ui.collapsible title="Dokumente" :expanded="false" :count="$documentCount">
			@forelse ($documents as $document)
				<x-row.document :document="$document" />
			@empty
				<p class="mt-16 italic">Es sind noch keine Dokumente vorhanden.</p>
			@endforelse

			@if ($documentCount > $documents->count())
				{{-- `.mt-4x` and `.link-helper` — 16px above, grey on hover
				     rather than teal. --}}
				<div class="mt-16">
					<a href="{{ \App\Support\SiteUrl::studentDocuments() }}"
						class="inline-flex items-center gap-8 transition-colors hover:text-gray-400">
						<span>Alle Dokumente anzeigen</span>
						<x-icon.arrow-right />
					</a>
				</div>
			@endif
		</x-ui.collapsible>
	</div>

	{{-- The two confirmations *Annullieren* needs, and the rental add. One
	     component, once per page ([[09-public-site]]). --}}
	<x-dialog.booking />
</x-layout.site>
