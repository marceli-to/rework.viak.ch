<x-layout.site title="Registrieren" auth>
	@php
		/*
		 * **`männlich / weiblich / andere`**, which is what production serves —
		 * read off the live `/de/registration` on 2026-09-22, and the same three
		 * strings `genders.description` holds in the legacy database.
		 *
		 * This said `Frau / Herr / Divers` until then. Defensible as copy — the
		 * field exists for the salutation on an invoice ([[Gender]]) — but it
		 * was neither measured nor recorded, and the portal's own profile form
		 * would then have disagreed with this one. It comes from
		 * `Gender::label()` now, so there is one place to change it if VIAK
		 * decides the salutation is the better wording.
		 */
		$genders = collect(\App\Enums\Gender::cases())
			->mapWithKeys(fn (\App\Enums\Gender $gender) => [$gender->value => $gender->label()])
			->all();

		$operatingSystems = [
			\App\Enums\OperatingSystem::Windows->value => 'Windows',
			\App\Enums\OperatingSystem::MacOS->value => 'macOS',
			\App\Enums\OperatingSystem::Other->value => 'anderes',
		];

		$chosenSystems = (array) old('operating_systems', []);
	@endphp

	{{--
		`frontend/register/Index.vue`, rebuilt as a form: the same seventeen
		fields in the same order. Legacy's was a Vue island posting JSON and
		painting its own validation errors; this posts to Fortify and Laravel
		paints them, which is the same page with 200 fewer lines behind it.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Registrieren</h1>

			<div class="mt-20 lg:mt-40">
				<a href="{{ route('login') }}" class="inline-flex flex-col items-start hover:text-teal">
					<span>Bereits registriert?</span>
					<x-icon.arrow-right class="mt-8" />
				</a>
			</div>
		</x-slot:aside>

		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<form method="POST" action="{{ route('register') }}">
			@csrf

			<x-site.select name="gender" label="Geschlecht" :options="$genders" placeholder="Bitte wählen..." required />

			<x-site.field name="first_name" label="Vorname" required autocomplete="given-name" />
			<x-site.field name="last_name" label="Nachname" required autocomplete="family-name" />
			<x-site.field name="company" label="Firma" autocomplete="organization" />
			<x-site.field name="phone" label="Telefon" type="tel" required autocomplete="tel" />

			{{--
				Street and number share a row from `sm`, **half and half**.

				This was 9/3, on the reasoning that a house number needs less
				room than a street name — true, and not what the site does.
				Measured on the live `/de/registration`, 2026-09-22: both
				`.form-group`s are `span-6` and both render **329px** of the
				1068 column. Legacy writes the pair as `class="span-6"` twice,
				here and on the profile and address forms alike.
			--}}
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-site.field name="street" label="Strasse" required autocomplete="address-line1" />
				</div>
				<div class="sm:col-span-6">
					<x-site.field name="street_no" label="Nr." maxlength="5" />
				</div>
			</div>

			{{-- Half and half again, and measured the same way — 329px each
			     on production, not the 4/8 this carried. --}}
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-site.field name="zip" label="PLZ" required maxlength="10" autocomplete="postal-code" />
				</div>
				<div class="sm:col-span-6">
					<x-site.field name="city" label="Ort" required autocomplete="address-level2" />
				</div>
			</div>

			<x-site.select
				name="country_code"
				label="Land"
				:options="\App\Actions\Accounts\RegisterUser::countries()"
				:value="'ch'"
				required
			/>

			<x-site.field name="email" label="E-Mail" type="email" required autocomplete="email" />

			{{-- **Paste is blocked here, as it is on the live site.** A pasted
			     confirmation confirms the typo rather than the address, and every
			     password reset for the life of the account goes to whatever was
			     typed. --}}
			<x-site.field
				name="email_confirmation"
				label="E-Mail wiederholen"
				type="email"
				required
				autocomplete="off"
				onpaste="event.preventDefault();"
			/>

			<x-site.field name="password" label="Passwort" type="password" required autocomplete="new-password" />
			<x-site.field name="password_confirmation" label="Passwort wiederholen" type="password" required autocomplete="new-password" />

			{{-- Which machines the student works on. It is what decides whether a
			     rented laptop is any use to them. --}}
			<div class="mb-16 lg:mb-32">
				<span class="mb-4 block text-md sm:text-lg lg:text-xl">Betriebssystem *</span>

				<div class="flex flex-col gap-8">
					@foreach ($operatingSystems as $value => $label)
						<x-site.checkbox
							name="operating_systems[]"
							:id="'os_'.$value"
							:value="$value"
							:checked="in_array($value, $chosenSystems, true)"
						>{{ $label }}</x-site.checkbox>
					@endforeach
				</div>

				@error('operating_systems')
					<div class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
				@enderror
			</div>

			<div class="mb-16 flex flex-col gap-8 lg:mb-32">
				<x-site.checkbox name="accept_tos" :checked="(bool) old('accept_tos')">
					Ich akzeptiere die AGB *
				</x-site.checkbox>

				<x-site.checkbox name="subscribe_newsletter" :checked="(bool) old('subscribe_newsletter')">
					Ich möchte den Newsletter abonnieren
				</x-site.checkbox>
			</div>

			@error('accept_tos')
				<div class="mb-16 text-md text-danger lg:text-lg">{{ $message }}</div>
			@enderror

			<x-site.button type="submit" class="w-full">Registrieren</x-site.button>
		</form>
	</x-site.article>
</x-layout.site>
