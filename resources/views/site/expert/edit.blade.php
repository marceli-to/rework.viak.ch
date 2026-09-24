@php
	$locale = app()->getLocale();

	$genderOptions = collect($genders)
		->mapWithKeys(fn (\App\Enums\Gender $gender) => [$gender->value => $gender->label()])
		->all();

	$countryOptions = $countries->mapWithKeys(fn ($country) => [$country->code => $country->name])->all();
@endphp

<x-layout.site title="Profil bearbeiten" heading="Profil bearbeiten" auth>
	{{--
		The expert's profile form — the writing half of
		`backend/expert/views/Index.vue` ([[08-accounts]], [[09-public-site]]).

		**`site/student/edit.blade.php` minus *Rechnungsadressen***, which is
		exactly the difference legacy's two copies of this form have: an expert is
		not a customer and holds no invoice addresses. Every other field, in the
		same order, with the same rules behind it — one [[UpdateProfileRequest]]
		and one [[UpdateProfile]] for both, where legacy keeps three controllers
		that differ only in which fields they validate and each change an email
		address without confirming it ([[08-accounts]], finding 2).

		Which means nothing here links out of the form, and the *Zurück* in the
		aside is the only way off it — so this screen does not have the bug that
		made the student's a screen. It is one anyway: the two forms should not
		disagree about what they are, and a toggle is state the back button
		cannot see ([[SiteUrl::expertProfileEdit]]).
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Profil bearbeiten</h1>

			<x-ui.back-link :href="\App\Support\SiteUrl::expertPortal()" />
		</x-slot:aside>

		@if ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		{{-- The POST lands on this same URL, so a validation failure comes back
		     to the form by itself. --}}
		<form method="POST" action="{{ route($locale.'.expert.profile.update') }}">
			@csrf

			<x-form.select name="gender" label="Geschlecht" :options="$genderOptions"
				placeholder="Bitte wählen..." :value="$user->gender?->value" required />

			<x-form.field name="first_name" label="Vorname" :value="$user->first_name" required autocomplete="given-name" />
			<x-form.field name="last_name" label="Nachname" :value="$user->last_name" required autocomplete="family-name" />
			<x-form.field name="company" label="Firma" :value="$user->company" autocomplete="organization" />
			<x-form.field name="phone" label="Telefon" type="tel" :value="$user->phone" required autocomplete="tel" />

			{{-- `span-6` and `span-6`, as on the registration form and the
			     student's — 329px each, measured 2026-09-22. --}}
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

			{{-- `.line-after`: a 1px black rule under the group and 32px below
			     it. --}}
			<div class="mb-32 border-b border-black pb-16">
				<x-form.checkbox name="subscribe_newsletter" :checked="(bool) old('subscribe_newsletter', $user->subscribe_newsletter)">
					Ich möchte den Newsletter abonnieren.
				</x-form.checkbox>
			</div>

			{{-- *Zugangsdaten*, shut — legacy's one collapsible inside this form,
			     at `mt-6x sm:mt-9x md:mt-12x`. The student's carries a second
			     one above it for the addresses; without it this is the whole
			     tail of the form. --}}
			<x-ui.collapsible title="Zugangsdaten" class="mt-24 sm:mt-36 lg:mt-48" :expanded="false">
				<x-form.field name="email" label="E-Mail" type="email" :value="$user->email" autocomplete="email" />

				<x-form.field name="password" label="Passwort" type="password"
					hint="min. 8 Zeichen" autocomplete="new-password" />
				<x-form.field name="password_confirmation" label="Passwort wiederholen" type="password"
					autocomplete="new-password" />

				{{-- **The field legacy has nowhere**, on any of its three copies
				     of this form: changing an address or a password asked for
				     nothing but the open session ([[UpdateProfile]]). Required
				     only when the address is actually *changing* or a password
				     is given ([[UpdateProfileRequest]]). --}}
				<x-form.field name="current_password" label="Aktuelles Passwort" type="password"
					hint="Nur nötig, um E-Mail oder Passwort zu ändern."
					autocomplete="current-password" />
			</x-ui.collapsible>

			{{-- Full width of the `span-8` column, which is what production
			     renders, and the button's own margin is what spaces *Abbrechen*
			     — 16px, 32 from `lg`. --}}
			<div class="mb-16 lg:mb-32">
				<x-ui.button type="submit" class="w-full">Speichern</x-ui.button>
			</div>

			{{-- `.form-helper`: 14/16/18 and italic. A link rather than a button
			     — nothing has been posted, so leaving the form *is* going back to
			     the screen it came from. --}}
			<a href="{{ \App\Support\SiteUrl::expertPortal() }}"
				class="inline-block text-md italic transition-colors hover:text-teal sm:text-lg lg:text-xl">Abbrechen</a>
		</form>
	</x-layout.article>
</x-layout.site>
