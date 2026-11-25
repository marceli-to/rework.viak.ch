@php($title = $booked ? 'Buchung abgeschlossen' : 'Bestellung abgeschlossen')

<x-layout.site :title="$title" auth>
	{{--
		`web/pages/checkout/confirmation.blade.php`, rebuilt 1:1 — and unlike the
		four steps this one really is a Blade page on the live site too.

		`article.content-text` with both halves this time, so
		`<x-layout.article>` rather than the bare aside the steps use: the heading
		and *Zum Profil* in the `span-4`, the thank-you in the `span-8`.

		**And the licence order under it** ([[13-checkout]]): paid, free, by
		invoice, being confirmed by Stripe, or waiting, with *Jetzt bezahlen*
		for the last two (#45, [[LicencePayment]]). A basket of licences only is *Bestellung abgeschlossen*.
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">{{ $title }}</h1>

			{{-- `sm:mt-5x md:mt-10x` — 20px, then 40 from `lg`. The label sits
			     above the arrow, which is legacy's `icon-arrow-right:below`. --}}
			<div class="mt-20 lg:mt-40">
				<a href="{{ \App\Support\SiteUrl::customerPortal() }}" title="Zum Profil"
					class="inline-flex flex-col items-start hover:text-teal">
					<span>Zum Profil</span>
					<x-icon.arrow-right class="mt-8" />
				</a>
			</div>
		</x-slot:aside>

		{{--
			**Emptying the basket is the browser's job, and nothing else can do
			it.** The selection is in `localStorage`, so the server cannot clear
			it on the way past — this page tells Alpine to, once, on arrival.

			Legacy never had to: `BasketStore` was a session bag that a
			completed checkout simply dropped. That is also why nothing in the
			old data records that two bookings were one purchase, which is what
			[[Checkout]] exists to fix ([[06-bookings]]).
		--}}
		<div x-data x-init="$store.basket.clear()">
			@if ($booked)
				<p>Vielen Dank für Deine Buchung. Du erhältst in den nächsten Minuten eine
					Bestätigung per E-Mail.</p>
			@endif

			@if ($order)
				<p @class(['mt-16' => $booked])>
					@if ($paid && $order->invoice)
						Vielen Dank für Deine Bestellung {{ $order->number }}. Deine Zahlung ist
						eingegangen, wir senden die Lizenzen an {{ $email }}.
					@elseif ($paid)
						Vielen Dank für Deine Bestellung {{ $order->number }}. Wir senden die
						Lizenzen an {{ $email }}.
					@elseif ($byInvoice && $order->invoice)
						Vielen Dank für Deine Bestellung {{ $order->number }}. Die Rechnung ist per
						E-Mail unterwegs, Du kannst sie mit dem QR-Einzahlungsschein, per
						Kreditkarte oder mit TWINT bezahlen. Die Lizenzen senden wir an {{ $email }}.
					@elseif ($confirming)
						Vielen Dank für Deine Bestellung {{ $order->number }}. Sobald Deine Zahlung
						bestätigt ist, erhältst Du eine E-Mail. Die Lizenzen senden wir an {{ $email }}.
					@else
						Deine Bestellung {{ $order->number }} ist erfasst, aber noch nicht bezahlt.
						Die Lizenzen senden wir an {{ $email }}.
					@endif
				</p>

				@if (! $paid && ! $confirming && $order->invoice)
					<p class="mt-16">
						<a href="{{ \App\Support\SiteUrl::invoicePayment($order->invoice->uuid) }}"
							class="underline decoration-1 underline-offset-[3px] hover:no-underline">Jetzt bezahlen</a>
						oder später in Deinem Profil unter Bestellungen.
					</p>
				@endif
			@endif

			{{--
				*Wie wurdest du auf uns aufmerksam?* ([[11-tracking]] step 4): after
				the purchase, so it cannot cost one, optional by being here, and
				asked once per customer. Not legacy's: legacy has no such question,
				so it is drawn from the site's own controls, the payment step's
				radios and the form field, and nothing new.
			--}}
			@if (session('heard_from_thanks'))
				<p id="aufmerksam" class="mt-16">Danke für Deine Antwort.</p>
			@elseif ($askHeardFrom)
				<form id="aufmerksam" method="POST" action="{{ \App\Support\SiteUrl::checkout('confirmation') }}"
					class="mt-32 lg:mt-40" x-data="{ choice: @js(old('heard_from')) }">
					@csrf
					<fieldset>
						<legend class="font-bold">Wie bist Du auf uns aufmerksam geworden?</legend>

						<div class="mt-16 space-y-8">
							@foreach ($heardFromOptions as $key => $label)
								<x-form.checkbox type="radio" id="heard_from_{{ $key }}" name="heard_from" :value="$key"
									:checked="old('heard_from') === $key" x-model="choice">
									{{ $label }}
								</x-form.checkbox>
							@endforeach
						</div>

						@error('heard_from')
							<div class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
						@enderror
					</fieldset>

					<div class="mt-16" x-cloak x-show="choice === 'andere'">
						<x-form.field name="heard_from_other" label="Wie?" maxlength="255" />
					</div>

					<div class="mt-16">
						<x-ui.button type="submit">Senden</x-ui.button>
					</div>
				</form>
			@endif

			<p class="mt-16">
				<a href="{{ \App\Support\SiteUrl::courses() }}" title="Zum Kursangebot"
					class="underline decoration-1 underline-offset-[3px] hover:no-underline">
					Zum Kursangebot
				</a>
			</p>
		</div>
	</x-layout.article>
</x-layout.site>
