@php
	$locale = app()->getLocale();

	$genderOptions = collect($genders)
		->mapWithKeys(fn (\App\Enums\Gender $gender) => [$gender->value => $gender->label()])
		->all();

	$countryOptions = $countries->mapWithKeys(fn ($country) => [$country->code => $country->name])->all();
@endphp

<x-layout.site title="Profil bearbeiten" heading="Profil bearbeiten" auth>
	{{--
		The profile form, on a screen of its own — the writing half of
		`backend/student/views/Index.vue` ([[08-accounts]], [[09-public-site]]).

		**A sibling of the address screens, not a state of the profile** (Marcel,
		2026-09-22). Same shape as `site/student/address.blade.php`: an aside
		with the heading and a *Zurück*, and the form in the `span-8` column.
		Nothing else — no Merkliste, no course lists, no Dokumente. A form asking
		for a phone number has no use for them, and the *Zurück* is the way back
		to all four.

		Which is also why the aside carries *Zurück* rather than the profile's
		*Logout*: this is a screen you came from somewhere and will go back to,
		and offering to end the session from the middle of an unsaved form is not
		the exit anyone is looking for.

		Legacy toggles this form in place off `isEdit` and keeps the whole page
		around it. That state does not survive leaving the page, and
		*Rechnungsadressen* lives inside the form with links to screens of their
		own — so adding an address landed you back on a shut panel with the new
		address invisible in it ([[SiteUrl::studentProfileEdit]]).

		There is no pencil here. On the profile it opens this; here *Zurück*
		closes it, and two controls doing one job is what the toggle was.
	--}}
	<x-layout.article>
		<x-slot:aside>
			{{-- Hidden below `sm` — legacy's `xs:hide` — because the header
			     already carries the page title on a phone. --}}
			<h1 class="hidden font-bold text-teal sm:block">Profil bearbeiten</h1>

			<x-ui.back-link :href="\App\Support\SiteUrl::studentPortal()" />
		</x-slot:aside>

		@if ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		{{-- The POST lands on this same URL, so a validation failure comes back
		     to the form by itself. --}}
		<form method="POST" action="{{ route($locale.'.student.profile.update') }}">
			@csrf

			<x-form.select name="gender" label="Geschlecht" :options="$genderOptions"
				placeholder="Bitte wählen..." :value="$user->gender?->value" required />

			<x-form.field name="first_name" label="Vorname" :value="$user->first_name" required autocomplete="given-name" />
			<x-form.field name="last_name" label="Nachname" :value="$user->last_name" required autocomplete="family-name" />
			<x-form.field name="company" label="Firma" :value="$user->company" autocomplete="organization" />
			<x-form.field name="phone" label="Telefon" type="tel" :value="$user->phone" required autocomplete="tel" />

			{{-- `span-6` and `span-6`, as on the registration form and as
			     production renders both — 329px each, measured 2026-09-22. --}}
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-form.field name="street" label="Strasse" :value="$user->street" required autocomplete="address-line1" />
				</div>
				<div class="sm:col-span-6">
					<x-form.field name="street_no" label="Nr." :value="$user->street_no" maxlength="5" />
				</div>
			</div>

			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-form.field name="zip" label="PLZ" :value="$user->zip" required maxlength="10" autocomplete="postal-code" />
				</div>
				<div class="sm:col-span-6">
					<x-form.field name="city" label="Ort" :value="$user->city" required autocomplete="address-level2" />
				</div>
			</div>

			<x-form.select name="country_code" label="Land" :options="$countryOptions"
				:value="$user->country_code" required />

			{{-- `.line-after`: a 1px black rule under the group and 32px
			     below it. --}}
			<div class="mb-32 border-b border-black pb-16">
				<x-form.checkbox name="subscribe_newsletter" :checked="(bool) old('subscribe_newsletter', $user->subscribe_newsletter)">
					Ich möchte den Newsletter abonnieren.
				</x-form.checkbox>
			</div>

			{{--
				*Rechnungsadressen*, open, then *Zugangsdaten*, shut — legacy's
				two collapsibles inside the form, at `mt-14x` (56px) and
				`mt-8x sm:mt-12x`.

				The addresses are still **links out of the form**, as they are in
				legacy, and anything typed above is lost by going to one. What is
				fixed is the way back: those screens return *here* rather than to
				a profile that does not show them ([[StudentAddressController]]).
			--}}
			<x-ui.collapsible title="Rechnungsadressen" class="mt-56" :expanded="true">
				@forelse ($addresses as $address)
					{{--
						`.stacked-list-item` with the pencil pinned to its right
						— measured at 17px down from the row's top border, which
						is the icon's own `mt-2x sm:mt-4x` plus the 1px rule.

						**The first row is pulled up at `lg`.** `Index.vue` puts
						`md:mt-3x` on `index == 0` and nothing on the rest, so
						the gap from the heading is **12px** at desktop against
						the 32 every other row keeps — measured on the live
						stylesheet, 2026-09-22. It is a desktop-only rule:
						below `bp-md` the first row keeps the row margin like
						any other, which is why this is `lg:mt-12` rather than
						a margin removed.
					--}}
					<article @class([
						'relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl',
						'lg:mt-12' => $loop->first,
					])>
						{{ $address->summary() }}

						<a href="{{ \App\Support\SiteUrl::studentAddressEdit($address->uuid) }}"
							title="Adresse bearbeiten"
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
						title="Adresse hinzufügen"
						class="block transition-colors hover:text-teal">
						<x-icon.plus class="w-16" />
					</a>
				</div>
			</x-ui.collapsible>

			<x-ui.collapsible title="Zugangsdaten" class="mt-32 sm:mt-48" :expanded="false">
				<x-form.field name="email" label="E-Mail" type="email" :value="$user->email" autocomplete="email" />

				<x-form.field name="password" label="Passwort" type="password"
					hint="min. 8 Zeichen" autocomplete="new-password" />
				<x-form.field name="password_confirmation" label="Passwort wiederholen" type="password"
					autocomplete="new-password" />

				{{--
					**The field legacy has nowhere.** Changing an address or a
					password here asked for nothing but the open session, on all
					three of its role-specific copies of this form — so a session
					left on a shared machine was enough to take an account over
					permanently, and the new address inherited verified status
					without ever being proven ([[UpdateProfile]]).

					Required only when the address is actually **changing** or a
					password is given, which is what [[UpdateProfileRequest]]
					enforces; leaving all three as they are saves the rest of the
					form as before.
				--}}
				<x-form.field name="current_password" label="Aktuelles Passwort" type="password"
					hint="Nur nötig, um E-Mail oder Passwort zu ändern."
					autocomplete="current-password" />
			</x-ui.collapsible>

			{{--
				Full width of the `span-8` column, which is what production
				renders — `%btn` never states a width and an `<a>` in block flow
				fills its parent (measured 699px, 2026-09-22). A real `<button>`
				sizes to `fit-content` whatever its display, so here it has to be
				said.

				The button sits in a `.form-group` like every field above it, and
				**its margin is what spaces *Abbrechen*** — 16px, 32 from `lg`.
				This carried `mt-32` above and `mt-16` below instead, which put
				16px between them at every width where production has 32.
			--}}
			<div class="mb-16 lg:mb-32">
				<x-ui.button type="submit" class="w-full">Speichern</x-ui.button>
			</div>

			{{-- `.form-helper`: **14/16/18 and italic**, measured on the live
			     stylesheet 2026-09-22. It had no size and inherited the page's
			     24px, which made it bigger than the button above it. A link
			     rather than a button — nothing has been posted, so leaving the
			     form *is* going back to the screen it came from. --}}
			<a href="{{ \App\Support\SiteUrl::studentPortal() }}"
				class="inline-block text-md italic transition-colors hover:text-teal sm:text-lg lg:text-xl">Abbrechen</a>
		</form>
	</x-layout.article>
</x-layout.site>
