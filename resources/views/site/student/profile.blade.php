<x-layout.site title="Profil" heading="Mein Profil" auth>
	@php
		$locale = app()->getLocale();

		$genderOptions = collect($genders)
			->mapWithKeys(fn (\App\Enums\Gender $gender) => [$gender->value => $gender->label()])
			->all();

		$countryOptions = $countries->mapWithKeys(fn ($country) => [$country->code => $country->name])->all();
	@endphp

	{{--
		*Mein Profil* — `backend/student/views/Index.vue`, server-rendered
		([[08-accounts]], [[09-public-site]]).

		**Teal-gutted**, because legacy's `web/pages/user/student/index.blade.php`
		sets `is-auth` on `<html>`, exactly as the checkout and the auth screens
		do.

		Four collapsibles under the profile block, in two containers — which is
		legacy's grouping and not decoration: the three course lists belong
		together and *Dokumente* is its own subject.

		**`$editing` is a URL, not a toggle** (Marcel, 2026-09-22).
		`/de/student/profil/bearbeiten` renders the form where the address block
		otherwise is, and everything below it is unchanged — which is legacy's
		page, since there the form replaces the same block. What legacy does not
		have is a way back into it: `isEdit` is component state, and
		*Rechnungsadressen* lives inside the form with links to screens of their
		own, so adding an address landed you back on a shut panel with the new
		address invisible in it ([[SiteUrl::studentProfileEdit]]).

		It also leaves this screen with **no JavaScript of its own**. The pencil
		is a link, *Abbrechen* is a link, and the browser's back button does what
		it looks like it should.
	--}}
	<div>
		<x-site.article>
			{{-- `.icon-edit` is `position: absolute; top: 0; right: 0` at 18×18,
			     and `article.content-text` is the `position: relative` it hangs
			     off — measured 2026-09-22. It sits outside `.text__body`, so it
			     is over the aside rather than over the form.

			     A link where legacy has an `<a href="">` that toggles: it goes
			     to the form, and on the form it comes back. --}}
			<a href="{{ $editing ? \App\Support\SiteUrl::studentPortal() : \App\Support\SiteUrl::studentProfileEdit() }}"
				class="absolute top-0 right-0 block transition-colors hover:text-teal"
				title="{{ $editing ? 'Bearbeiten abbrechen' : 'Profil bearbeiten' }}">
				<x-icon.edit class="w-18" />
			</a>

			<x-slot:aside>
				{{-- Hidden below `sm` — legacy's `xs:hide` — because the header
				     already carries the page title on a phone. --}}
				<h1 class="hidden font-bold text-teal sm:block">Mein Profil</h1>

				<x-site.back-link :href="route('logout')" label="Logout" direction="right" method="post" />
			</x-slot:aside>

			@if (session('status'))
				<x-site.toast variant="success">{{ session('status') }}</x-site.toast>
			@endif

			@if ($errors->any())
				<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
			@endif

			@unless ($editing)
				{{-- Reading: the address as a block, then the email. Legacy
				     renders it into a `<pre>` from a server-built string; the
				     lines are columns here, so they can be styled and read
				     out. --}}
				<div>
					<div>
						@if ($user->company){{ $user->company }}<br>@endif
						{{ $user->name }}<br>
						{{ $user->street }} {{ $user->street_no }}<br>
						{{ $user->zip }} {{ $user->city }}
						@if ($user->country && $user->country_code !== 'ch')<br>{{ $user->country->name }}@endif
					</div>
					<div><a href="mailto:{{ $user->email }}" class="hover:text-teal">{{ $user->email }}</a></div>
				</div>
			@else
				{{-- Writing. The POST lands on this same URL, so a validation
				     failure comes back to the form by itself. --}}
				<form method="POST" action="{{ route($locale.'.student.profile.update') }}">
				@csrf

				<x-site.select name="gender" label="Geschlecht" :options="$genderOptions"
					placeholder="Bitte wählen..." :value="$user->gender?->value" required />

				<x-site.field name="first_name" label="Vorname" :value="$user->first_name" required autocomplete="given-name" />
				<x-site.field name="last_name" label="Nachname" :value="$user->last_name" required autocomplete="family-name" />
				<x-site.field name="company" label="Firma" :value="$user->company" autocomplete="organization" />
				<x-site.field name="phone" label="Telefon" type="tel" :value="$user->phone" required autocomplete="tel" />

				{{-- `span-6` and `span-6`, as on the registration form and as
				     production renders both — 329px each, measured
				     2026-09-22. --}}
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-6">
						<x-site.field name="street" label="Strasse" :value="$user->street" required autocomplete="address-line1" />
					</div>
					<div class="sm:col-span-6">
						<x-site.field name="street_no" label="Nr." :value="$user->street_no" maxlength="5" />
					</div>
				</div>

				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-6">
						<x-site.field name="zip" label="PLZ" :value="$user->zip" required maxlength="10" autocomplete="postal-code" />
					</div>
					<div class="sm:col-span-6">
						<x-site.field name="city" label="Ort" :value="$user->city" required autocomplete="address-level2" />
					</div>
				</div>

				<x-site.select name="country_code" label="Land" :options="$countryOptions"
					:value="$user->country_code" required />

				{{-- `.line-after`: a 1px black rule under the group and 32px
				     below it. --}}
				<div class="mb-32 border-b border-black pb-16">
					<x-site.checkbox name="subscribe_newsletter" :checked="(bool) old('subscribe_newsletter', $user->subscribe_newsletter)">
						Ich möchte den Newsletter abonnieren.
					</x-site.checkbox>
				</div>

				{{--
					*Rechnungsadressen*, open, then *Zugangsdaten*, shut —
					legacy's two collapsibles inside the form, at `mt-14x` (56px)
					and `mt-8x sm:mt-12x`.

					The addresses are **links out of the form**, which is
					legacy's shape too: its router-links leave the profile for a
					page of their own, and anything typed in the form above is
					lost either way. Said plainly rather than fixed, because
					fixing it means either a nested form or a dialog, and the
					second is a different screen with a different job
					([[StudentAddressController]]).
				--}}
				<x-site.collapsible title="Rechnungsadressen" class="mt-56" :expanded="true">
					@forelse ($addresses as $address)
						{{-- `.stacked-list-item` with the pencil pinned to its
						     right — measured at 17px down from the row's top
						     border, which is the icon's own `mt-2x sm:mt-4x`
						     plus the 1px rule. --}}
						<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
							{{ $address->summary() }}

							<a href="{{ \App\Support\SiteUrl::studentAddressEdit($address->uuid) }}"
								title="Rechnungsadresse bearbeiten"
								class="absolute top-16 right-0 mt-2 block transition-colors hover:text-teal sm:top-16 sm:mt-4">
								<x-icon.edit class="w-18" />
							</a>
						</article>
					@empty
						<p class="mt-16 italic">Du hast noch keine Rechnungsadresse erfasst.</p>
					@endforelse

					{{-- `.flex.justify-start.mt-6x` around a 16×16 plus. --}}
					<div class="mt-24 flex justify-start">
						<a href="{{ \App\Support\SiteUrl::studentAddressCreate() }}"
							title="Rechnungsadresse erfassen"
							class="block transition-colors hover:text-teal">
							<x-icon.plus class="w-16" />
						</a>
					</div>
				</x-site.collapsible>

				<x-site.collapsible title="Zugangsdaten" class="mt-32 sm:mt-48" :expanded="false">
					<x-site.field name="email" label="E-Mail" type="email" :value="$user->email" autocomplete="email" />

					<x-site.field name="password" label="Passwort" type="password"
						hint="min. 8 Zeichen" autocomplete="new-password" />
					<x-site.field name="password_confirmation" label="Passwort wiederholen" type="password"
						autocomplete="new-password" />

					{{--
						**The field legacy has nowhere.** Changing an address or
						a password here asked for nothing but the open session,
						on all three of its role-specific copies of this form —
						so a session left on a shared machine was enough to take
						an account over permanently, and the new address
						inherited verified status without ever being proven
						([[UpdateProfile]]).

						Required only when one of the two above is filled in,
						which is what [[UpdateProfileRequest]] enforces; leaving
						all three blank saves the rest of the form as before.
					--}}
					<x-site.field name="current_password" label="Aktuelles Passwort" type="password"
						hint="Nur nötig, um E-Mail oder Passwort zu ändern."
						autocomplete="current-password" />
				</x-site.collapsible>

				{{-- Full width of the `span-8` column, which is what production
				     renders — `%btn` never states a width and an `<a>` in block
				     flow fills its parent (measured 699px, 2026-09-22). A real
				     `<button>` sizes to `fit-content` whatever its display, so
				     here it has to be said. --}}
				<div class="mt-32">
					<x-site.button type="submit" class="w-full">Speichern</x-site.button>
				</div>

				{{-- `.form-helper`: italic, at the page's own size. A link
				     rather than a button — nothing has been posted, so leaving
				     the form *is* going back to the screen it came from. --}}
				<a href="{{ \App\Support\SiteUrl::studentPortal() }}"
					class="mt-16 inline-block italic transition-colors hover:text-teal">Abbrechen</a>
				</form>
			@endunless
		</x-site.article>
	</div>

	{{-- `.stacked-list-container` / `.collapsible-container`: 48px above, 64
	     from `lg`. The 64px under each collapsible collapses against the next
	     container's 48, so the gap between the two groups is 64 rather than
	     112 — legacy's arithmetic, kept by keeping its markup. --}}
	<div class="mt-48 lg:mt-64">
		<x-site.collapsible title="Merkliste" :expanded="false" :count="$bookmarks->count()">
			@forelse ($bookmarks as $event)
				{{-- The heart and the basket buttons, which is what a bookmark
				     row is for. `x-site.event-card` draws the same pair on the
				     course page and its Alpine components are the same ones. --}}
				<x-site.event-row :event="$event">
					<x-slot:icon>
						<button type="button"
							x-data="bookmark({ event: @js($event->uuid), saved: true })"
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
								<x-site.button x-show="! $store.basket.has(uuid)"
									@click="$store.basket.book(uuid, rentals)">Buchen</x-site.button>
								<x-site.button variant="secondary" x-cloak x-show="$store.basket.has(uuid)"
									@click="$store.basket.remove(uuid)">Entfernen</x-site.button>
							</div>
						@endif
					</x-slot:action>
				</x-site.event-row>
			@empty
				<p class="mt-16 italic">Deine Merkliste ist leer.</p>
			@endforelse
		</x-site.collapsible>

		<x-site.collapsible title="Gebuchte Kurse" :expanded="true" :count="$upcoming->count()">
			@forelse ($upcoming as $booking)
				<x-site.event-row :event="$booking->event" :booking="$booking">
					{{-- **Teal**, which is the icon's own colour in legacy —
					     `Checkmark.vue` hardcodes `fill="#46baba"`. The Blade
					     set normalises every icon to `currentColor` so a
					     `text-*` can reach it (`resources/css/README.md`), which
					     is right and means the colour has to be said here
					     instead of being smuggled in with the artwork. --}}
					<x-slot:icon><x-icon.checkmark class="text-teal" /></x-slot:icon>

					<x-slot:action>
						<x-site.button href="{{ \App\Support\SiteUrl::studentEvent($booking->event->uuid) }}"
							class="mb-8" title="Detail">Detail</x-site.button>

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
						<x-site.button variant="secondary"
							@click="$store.portal.askCancel({{ \Illuminate\Support\Js::from([
								'uuid' => $booking->uuid,
								'penalty' => $penalties[$booking->uuid]['applies'],
								'amount' => $penalties[$booking->uuid]['amount'],
								'rate' => $penalties[$booking->uuid]['rate'],
							]) }})">
							Annullieren
						</x-site.button>
					</x-slot:action>

					@if ($booking->has_rental && $booking->isEditable())
						<x-slot:rentalAction>
							<x-site.button variant="secondary"
								@click="$store.portal.askCancelRental({{ \Illuminate\Support\Js::from(['uuid' => $booking->uuid]) }})">
								Annullieren
							</x-site.button>
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
									<x-site.button variant="secondary"
										@click="$store.portal.addRental({{ \Illuminate\Support\Js::from($booking->uuid) }})">
										Buchen
									</x-site.button>
								</div>
							</div>
						</x-slot:rentalPrompt>
					@endif
				</x-site.event-row>
			@empty
				<p class="mt-16 italic">Du hast noch keine Kurse gebucht.</p>
			@endforelse
		</x-site.collapsible>

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
		<x-site.collapsible title="Absolvierte Kurse" :expanded="false" :count="$past->count()">
			@forelse ($past as $booking)
				<x-site.event-row :event="$booking->event" :booking="$booking">
					{{-- **Teal**, which is the icon's own colour in legacy —
					     `Checkmark.vue` hardcodes `fill="#46baba"`. The Blade
					     set normalises every icon to `currentColor` so a
					     `text-*` can reach it (`resources/css/README.md`), which
					     is right and means the colour has to be said here
					     instead of being smuggled in with the artwork. --}}
					<x-slot:icon><x-icon.checkmark class="text-teal" /></x-slot:icon>
					<x-slot:action>
						<x-site.button href="{{ \App\Support\SiteUrl::studentEvent($booking->event->uuid) }}"
							title="Detail">Detail</x-site.button>
					</x-slot:action>
				</x-site.event-row>
			@empty
				<p class="mt-16 italic">Du hast noch keine Kurse absolviert.</p>
			@endforelse
		</x-site.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		<x-site.collapsible title="Dokumente" :expanded="false" :count="$documentCount">
			@forelse ($documents as $document)
				<x-site.document-row :document="$document" />
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
		</x-site.collapsible>
	</div>

	{{-- The two confirmations *Annullieren* needs, and the rental add. One
	     component, once per page ([[09-public-site]]). --}}
	<x-site.booking-dialogs />
</x-layout.site>
