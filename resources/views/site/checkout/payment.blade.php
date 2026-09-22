<x-layout.site title="Mein Warenkorb" auth>
	{{--
		Step 3 of 4, from `frontend/checkout/views/Payment.vue`
		([[09-public-site]]).

		**It takes no payment.** The whole step is a paragraph of text and a
		discount-code field, because invoices are raised on `EventConfirmed`
		days or weeks later ([[03-invoices]]) — so there is no Stripe anywhere
		in this flow. `PaymentController` and its checkout session are a
		separate thing, for paying an invoice that already exists.

		**The code belongs to the basket, not to the session**, which is the one
		design decision on this page. Everything else the checkout remembers is
		an *answer* and lives in [[CheckoutSession]]; a discount code is part of
		what is being priced, and the basket is the browser's. `basket.js`
		already carries `code`, `/api/basket/price` already takes it, and
		[[CompleteCheckoutRequest]] already expects it in the payload — so
		putting it anywhere else would have made two sources of truth for one
		number. Nothing about the price is settled here: the server re-resolves
		the code and re-prices from scratch at step 4.
	--}}
	<article class="relative">
		<aside>
			<h1 class="hidden leading-[1.3] font-bold text-teal sm:block">Mein Warenkorb</h1>
		</aside>
	</article>

	<div class="mt-48 lg:mt-64" x-data="{
		code: $store.basket.code ?? '',
		busy: false,

		get invalid() {
			return $store.basket.error !== null;
		},

		/*
		 * Legacy checks the code before it lets you past, with
		 * `GET /api/discount-code/check/{code}` and a client-side
		 * `length < 12` guard in front of it — a wrong constant, since every
		 * one of the 102 codes is 14 characters. Here `applyCode()` prices the
		 * basket with the code, which is the same question asked of the thing
		 * that will actually answer it; a code that will not apply comes back
		 * 422 and `store.basket.error` says so.
		 */
		async next() {
			if (this.busy) return;
			this.busy = true;

			await $store.basket.applyCode(this.code.trim());

			this.busy = false;
			if (! this.invalid) window.location = @js(\App\Support\SiteUrl::checkout('summary'));
		},
	}">
		<header class="leading-[1.3] sm:grid sm:grid-cols-12 sm:gap-16 sm:text-lg lg:gap-40 lg:text-xl">
			<div class="mb-8 sm:col-span-4"><strong class="font-bold">Schritt 3/4</strong></div>
			<div class="mb-8 sm:col-span-8"><h2 class="font-bold">Zahlung</h2></div>
		</header>

		<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-4"><strong class="font-bold">Zahlungsoptionen</strong></div>
				<div class="sm:col-span-8">
					Unsere Rechnungen können entweder mittels QR-Einzahlungsschein oder per
					Kreditkarte bezahlt werden. Die Rechnungsstellung erfolgt, sobald die
					Durchführung eines Kurses feststeht.
				</div>
			</div>
		</div>

		<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				{{-- The label **is** the error: legacy swaps the heading for a
				     red *Gutschein-Code ist ungültig!* rather than putting a
				     message under the field. --}}
				<div class="sm:col-span-4">
					<strong class="font-bold" x-show="! invalid">Gutschein-Code</strong>
					<strong class="font-bold text-danger" x-cloak x-show="invalid">
						Gutschein-Code ist ungültig!
					</strong>
				</div>

				{{-- `span-4`, not `span-8` — the field takes a third of the row
				     and the last third stays empty. And `.is-plain` takes the
				     rule out from under it, so this is the one input on the
				     site with no line: `border-bottom: none; padding-top: 0`. --}}
				<div class="sm:col-span-4">
					<input type="text" name="discount_code" placeholder="Code eingeben"
						x-model="code"
						@focus="$store.basket.error = null"
						@keydown.enter.prevent="next()"
						class="block w-full bg-transparent pb-4 text-lg leading-[normal] font-bold outline-hidden sm:text-xl lg:text-3xl">
				</div>
			</div>
		</div>

		<footer class="mt-32 grid grid-cols-12 gap-16 border-t-2 border-gray-400 sm:mt-48 lg:gap-40">
			<div class="col-span-6 flex justify-start">
				<a href="{{ \App\Support\SiteUrl::checkout('address') }}"
					class="flex items-center py-12 leading-none transition-colors hover:text-teal sm:py-16 sm:text-xl lg:text-3xl">
					<x-icon.arrow-left />
					<span class="ml-8 sm:ml-12">Zurück</span>
				</a>
			</div>
			<div class="col-span-6 flex justify-end">
				<button type="button" @click="next()" :disabled="busy"
					class="flex items-center py-12 leading-none transition-colors hover:text-teal disabled:opacity-60 sm:py-16 sm:text-xl lg:text-3xl">
					<span class="mr-8 sm:mr-12">Weiter</span>
					<x-icon.arrow-right />
				</button>
			</div>
		</footer>
	</div>
</x-layout.site>
