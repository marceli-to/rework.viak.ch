@php
	/* The dialog reopens itself when the address it was submitting came back
	   invalid — otherwise the redirect lands on a closed dialog with the errors
	   hidden behind it. Keyed on the form's own fields, so an `invoice_address`
	   error from the step itself does not open it. */
	$addressFields = ['first_name', 'last_name', 'company', 'street', 'street_no', 'zip', 'city', 'country_code'];
	$formFailed = $errors->hasAny($addressFields);
@endphp

<x-layout.site title="Mein Warenkorb" auth>
	{{--
		Step 2 of 4, from `frontend/checkout/views/User.vue` and its
		`AddressForm.vue` ([[09-public-site]]).

		**Server-rendered, where legacy made two API calls to draw it.**
		`User.vue` fetches `/api/student` and then `/api/basket` before it can
		show a screen whose every value the server already had — the customer's
		own address, their saved invoice addresses, and which one they picked.
		Here the controller hands all three to the view, and the only JavaScript
		is the checkbox that reveals the picker and the dialog that adds to it.
	--}}

	{{-- Legacy raises this as a toast rather than a field error:
	     `$toast.open({ message: 'Bitte Rechnungsadresse auswählen', type:
	     'error' })`, from its own client-side check. Ours comes back off a
	     redirect, in the same bar. --}}
	@error('invoice_address')
		<x-site.toast>{{ $message }}</x-site.toast>
	@enderror

	<article class="relative">
		<aside>
			<h1 class="hidden leading-[1.3] font-bold text-teal sm:block">Mein Warenkorb</h1>
		</aside>
	</article>

	<div class="mt-48 lg:mt-64"
		x-data="{
			separate: {{ $selected || $errors->has('invoice_address') || $formFailed ? 'true' : 'false' }},
			dialog: {{ $formFailed ? 'true' : 'false' }},
		}">

		{{-- Same header as the basket, and the same 1.3 it has to say out loud
		     (`resources/css/README.md`). --}}
		<header class="leading-[1.3] sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
			<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 2/4</strong></div>
			<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Kontakt</h2></div>
		</header>

		{{--
			`.stacked-list-item` — `%stacked-list` with nothing added, so the
			same rule and rhythm as the basket's rows: 16/8, 32/16 from `sm`, a
			1px black rule, 1.5 then 1.4 on 16/18px type.
		--}}
		<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-4"><strong class="font-bold">Kursteilnehmer</strong></div>
				<div class="sm:col-span-8">
					{{-- Legacy prints this through `v-html` from an accessor
					     that builds `<br>`s into a string. Lines here — the
					     same lines ([[UserAddress]]). --}}
					@foreach ($user->addressLines() as $line)
						{{ $line }}@if (! $loop->last)<br>@endif
					@endforeach
				</div>
			</div>
		</div>

		<form method="POST" action="{{ \App\Support\SiteUrl::checkout('address') }}"
			class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			@csrf

			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-4">
					<strong class="font-bold">Rechnungsadresse</strong>

					{{-- `.text-xsmall` is 12/14/16 and sets a size and nothing
					     else, so it inherits the row's line height — which is
					     why no `leading-*` belongs here even though the sizes
					     bring Tailwind's own with them. `mt-4x`. --}}
					<p class="mt-16 text-xs leading-[inherit] sm:text-md lg:text-lg">
						Sollte Ihre Kursteilnahme vom RAV bezahlt werden, so geben Sie bitte die
						Adresse des RAVs ein, welche Sie gemeinsam mit dem Entscheid erhalten
						haben. Sie brauchen die Rechnung weder zu bezahlen noch ans RAV
						weiterzuleiten. Dies übernehmen alles wir. Sie müssen lediglich die
						korrekte Rechnungsadresse des RAV erfassen.
					</p>
				</div>

				<div class="sm:col-span-8">
					{{--
						**Checked means "same as the participant"**, so the
						control is inverted: ticking it *hides* the picker.
						Legacy binds `:checked="hasAdresses ? false : true"`,
						and most customers never touch it — 126 of 710 bookings
						use a separate address.
					--}}
					<div class="mb-12 flex items-center">
						<x-site.checkbox id="same_address" name="same_address"
							x-bind:checked="! separate"
							@change="separate = ! separate">
							<em class="italic">entspricht Teilnehmer-Adresse</em>
						</x-site.checkbox>
					</div>

					{{--
						`x-if`, not `x-show`. **A hidden `<select>` still posts
						its value** — `display: none` is not `disabled` — so
						ticking *entspricht Teilnehmer-Adresse* after picking an
						address would have billed the employer anyway. Taking it
						out of the DOM is the version that cannot be wrong.
					--}}
					<template x-if="separate">
						<div>
							@if ($addresses->isNotEmpty())
								<x-site.select
									name="invoice_address"
									placeholder="Bitte wählen..."
									:value="$selected?->uuid"
									:options="$addresses->mapWithKeys(fn ($address) => [$address->uuid => $address->summary()])->all()"
								/>
							@endif

							{{-- `mt-1x sm:mt-3x`, and `.link-underline` is a
							     1px underline 3px down that goes away on
							     hover. --}}
							<div class="mt-4 flex justify-between sm:mt-12">
								<button type="button" @click="dialog = true"
									class="flex items-center text-xs underline decoration-1 underline-offset-[3px] hover:no-underline sm:text-md lg:text-lg">
									<x-icon.plus size="tiny" class="mt-4 mr-8" />
									Adresse erfassen
								</button>

								<a href="{{ route('dashboard') }}"
									class="text-xs underline decoration-1 underline-offset-[3px] hover:no-underline sm:text-md lg:text-lg">
									Adressen verwalten
								</a>
							</div>
						</div>
					</template>
				</div>
			</div>

			{{--
				`.stacked-list-footer` again, but with **both** halves this
				time: its `> div` is `span-6`, the first pushed left and the
				second right. `btn-previous` is `btn-next` with the arrow on the
				other side and the span's margin on the other edge.
			--}}
			<footer class="mt-32 grid grid-cols-12 gap-16 border-t-2 border-gray-400 sm:mt-48 lg:gap-40">
				<div class="col-span-6 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::checkout('basket') }}"
						class="flex items-center py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
						<x-icon.arrow-left />
						<span class="ml-8 sm:ml-12">Zurück</span>
					</a>
				</div>
				<div class="col-span-6 flex justify-end">
					<button type="submit"
						class="flex items-center py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
						<span class="mr-8 sm:mr-12">Weiter</span>
						<x-icon.arrow-right />
					</button>
				</div>
			</footer>
		</form>

		{{--
			*Adresse erfassen*, in `.lightbox` — the bigger overlay, because
			this is a form rather than a question. Inside the step's `x-data`
			so it shares `dialog`; it is `position: fixed`, so where it sits in
			the document does not decide where it appears.

			**A real form post**, where legacy calls `/api/student/address` and
			splices the answer into the select. So the step works the way every
			other form on this site does, a validation error comes back through
			the session, and `StoreAddressRequest` is the same rule set the API
			uses. The new address is selected on return, which is the only
			reason to be typing one here.
		--}}
		<x-site.lightbox title="Adresse erfassen" show="dialog" close="dialog = false">
			<form method="POST" action="{{ \App\Support\SiteUrl::checkout('address').'/new' }}">
				@csrf

				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-6"><x-site.field name="first_name" label="Vorname" /></div>
					<div class="sm:col-span-6"><x-site.field name="last_name" label="Nachname" /></div>
				</div>

				<x-site.field name="company" label="Firma" />

				{{-- Strasse/Nr. and PLZ/Ort are `span-6` with **no breakpoint**
				     in legacy, so they sit side by side on a phone too — unlike
				     Vorname/Nachname above, which are `sm:span-6`. --}}
				<div class="grid grid-cols-12 gap-16 lg:gap-40">
					<div class="col-span-6"><x-site.field name="street" label="Strasse" required /></div>
					<div class="col-span-6"><x-site.field name="street_no" label="Nr." maxlength="5" /></div>
				</div>

				<div class="grid grid-cols-12 gap-16 lg:gap-40">
					<div class="col-span-6"><x-site.field name="zip" label="PLZ" required maxlength="10" /></div>
					<div class="col-span-6"><x-site.field name="city" label="Ort" required /></div>
				</div>

				<x-site.select name="country_code" label="Land" required
					:value="old('country_code', 'ch')"
					:options="$countries->mapWithKeys(fn ($country) => [$country->code => $country->name])->all()"
				/>

				{{-- `form-group` with `flex direction-column items-center`: the
				     button, then *Abbrechen* 16px under it, 24 from `lg`. --}}
				<div class="mb-16 flex flex-col items-center lg:mb-32">
					<x-site.button type="submit">Speichern</x-site.button>

					<button type="button" @click="dialog = false"
						class="mt-16 text-xs underline decoration-1 underline-offset-[3px] hover:no-underline sm:text-md lg:mt-24 lg:text-lg">
						Abbrechen
					</button>
				</div>
			</form>
		</x-site.lightbox>
	</div>
</x-layout.site>
