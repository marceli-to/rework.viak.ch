@php
	$locale = app()->getLocale();
	$editing = $address !== null;

	/*
	 * **Legacy's own words** — `Form.vue`'s `title()` computes *Adresse
	 * bearbeiten* and *Adresse hinzufügen*, and this said *Rechnungsadresse
	 * bearbeiten* / *…erfassen*, which was neither. The screen already sits
	 * under *Rechnungsadressen* and the box at the foot of it says *Adresse
	 * löschen*, so the longer word was saying it twice and disagreeing with the
	 * box while it did.
	 *
	 * Not the same word as the checkout's *Adresse erfassen* dialog, which is
	 * also legacy's — there it is a dialog and here it is a page, and they are
	 * allowed to differ because production does.
	 */
	$title = $editing ? 'Adresse bearbeiten' : 'Adresse hinzufügen';

	$countryOptions = $countries->mapWithKeys(fn ($country) => [$country->code => $country->name])->all();
@endphp

<x-layout.site :title="$title" :heading="$title" auth>
	{{--
		One saved invoice address — `backend/student/views/address/Form.vue`,
		which legacy routes as two views over one component and which is what
		this is.

		**A page, not the checkout's dialog.** The lightbox on checkout step 2
		exists so a customer mid-purchase does not lose the basket; here there
		is nothing to lose and legacy gives the form a screen of its own
		([[StudentAddressController]]).
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">{{ $title }}</h1>
			{{-- Back **into the form**, not to the profile that shows it shut.
			     This screen is reached from the *Rechnungsadressen* block inside
			     it, and landing anywhere else hides the address you just came
			     here to add ([[SiteUrl::studentProfileEdit]]). --}}
			<x-site.back-link :href="\App\Support\SiteUrl::studentProfileEdit()" />
		</x-slot:aside>

		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<form method="POST"
			action="{{ $editing
				? route($locale.'.student.address.update', $address)
				: route($locale.'.student.address.store') }}">
			@csrf
			@if ($editing) @method('PUT') @endif

			{{-- `span-6` and `span-6` throughout, which is what the live forms
			     render — measured 2026-09-22 on `/de/registration`, where all
			     four of these fields are 329px of the 1068 column. --}}
			{{--
				**None of these three carries a `*`**, because none of them is
				required on its own: it is a name pair **or** a firm (Marcel,
				2026-09-22 — [[StoreAddressRequest]]). A star on Vorname would
				say something the server does not enforce, and `required` on the
				input would stop the browser submitting a perfectly valid
				company-only address before the server ever saw it.

				The rule is said once, under *Firma*, where somebody who has left
				the names blank is looking.
			--}}
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-site.field name="first_name" label="Vorname" :value="$address?->first_name" autocomplete="given-name" />
				</div>
				<div class="sm:col-span-6">
					<x-site.field name="last_name" label="Nachname" :value="$address?->last_name" autocomplete="family-name" />
				</div>
			</div>

			<x-site.field name="company" label="Firma" :value="$address?->company"
				hint="Vor- und Nachname oder Firma angeben." autocomplete="organization" />

			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-site.field name="street" label="Strasse" :value="$address?->street" required autocomplete="address-line1" />
				</div>
				<div class="sm:col-span-6">
					<x-site.field name="street_no" label="Nr." :value="$address?->street_no" maxlength="5" />
				</div>
			</div>

			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-6">
					<x-site.field name="zip" label="PLZ" :value="$address?->zip" required maxlength="10" autocomplete="postal-code" />
				</div>
				<div class="sm:col-span-6">
					<x-site.field name="city" label="Ort" :value="$address?->city" required autocomplete="address-level2" />
				</div>
			</div>

			<x-site.select name="country_code" label="Land" :options="$countryOptions"
				:value="$address?->country_code ?? 'ch'" required />

			{{-- `.form-group` like every field above it — 16px below, 32 from
			     `lg`. No top margin: the `Land` select above already carries
			     its own. --}}
			<div class="mb-16 lg:mb-32">
				{{-- Full width, which is what production renders: `%btn` is
				     `display: flex` and never states a width, so an `<a>` in
				     block flow fills its column — measured at 699px on the
				     registration form's *Speichern* and 663 on *Löschen*,
				     2026-09-22. A real `<button>` does **not** inherit that,
				     because a form control sizes to `fit-content` whatever its
				     display, so the width has to be said
				     ([[x-site.button]]). --}}
				<x-site.button type="submit" class="w-full">Speichern</x-site.button>
			</div>
		</form>

		@if ($editing)
			{{--
				`.form-danger-zone.is-danger`: **a 2px `#ff2800` box** —
				`border: 2px solid`, all four sides — with everything inside in
				the same red and padding that steps 8 → 8/12/12/12 → 12/16/16/16.
				24px above it on a phone and 48 from `sm`.

				It was `border-y-2` until 2026-09-22, top and bottom only, which
				is what comes of measuring `borderTopWidth` and
				`borderBottomWidth` and calling it done. Without the sides there
				is nothing for the padding to hold the button away from, so
				*Löschen* read as full-bleed and the block stopped looking like a
				box at all.

				**And it sets its own type size**, 14/16/18, like every other
				block on this site that is not the page — left to inherit it read
				at the page's 24px, which is the same mistake *Abbrechen* made
				two fixes ago. The heading and the paragraph take it from here.

				A real form rather than legacy's dialog-then-`axios.delete`. The
				confirmation is the browser's own submit — which is weaker than a
				modal, and is why this is the only destructive control on the
				site that stands inside a labelled box saying what it does.

				**Nothing is actually destroyed.** The row is soft-deleted,
				because a booking froze a *copy* of the address rather than
				pointing at it ([[StudentAddressController::destroy]]).
			--}}
			<form method="POST" action="{{ route($locale.'.student.address.destroy', $address) }}"
				class="mt-24 border-2 border-danger p-8 text-md text-danger sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl">
				@csrf
				@method('DELETE')

				<h2 class="mb-8 font-bold sm:mb-16">Adresse löschen</h2>
				<p class="mb-12 lg:mb-16">Mit dieser Aktion wird diese Adresse gelöscht.</p>

				<div class="mt-12 sm:mt-24">
					<x-site.button type="submit" variant="danger" class="w-full">Löschen</x-site.button>
				</div>
			</form>
		@endif
	</x-site.article>
</x-layout.site>
