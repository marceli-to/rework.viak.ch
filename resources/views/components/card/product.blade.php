@props(['product', 'bare' => false])

@php
	$locale = app()->getLocale();
	$hosts = collect($product->hostNames());

	$licences = $product->variants->filter(fn ($variant) => $variant->listed)->values()->map(fn ($variant) => [
		'uuid' => $variant->uuid,
		'label' => $variant->shopLabel(),
		// A demo costs nothing (#35): *kostenlos*, as a free course date says it.
		'price' => (float) $variant->price > 0 ? 'CHF '.number_format((float) $variant->price, 2, '.', "'") : null,
		'min' => max(1, (int) $variant->min_quantity),
		'note' => $variant->getTranslation('note', $locale, false) ?: null,
		'platforms' => $variant->platforms?->map->label()->join(', '),
	]);
@endphp

{{--
	One product in a software page's *Lizenzen* ([[05-licences]]), drawn as
	*Aktuelle Kurse* is (`card/event.blade.php`): the same black rule, spacing
	and type. Picked by Marcel from four drafts, 2026-10-09.

	**The product is a heading and every licence a row of its own**, under a
	grey hairline, the way a course lists its events. Nothing to pick: each
	licence has its own *Anzahl* and button, so a long name wraps in its
	column instead of hiding in a select. From `lg` the row is `5 / 3 / 4`:
	the licence with its platforms, note and *Hostsoftware*; the price;
	*Anzahl* beside the button. The last needs `span-4` for the two side by
	side. *Alle Preise exkl. MWST* is said once, under the list, by the
	page. Between the licences the event's own
	spacing, 32px over the hairline and 16 under it from `sm`.

	**Between products twice the event's 32px** above the black rule (Marcel,
	2026-10-09): with a licence's hairline 32px under its row too, the
	products ran together. The first keeps the event's.

	The product's name is an `<h3>` in teal, under the collapsible's `<h2>`,
	and its maker on the same line at the right (Marcel, 2026-10-09). A phone
	too narrow for both wraps the maker under the name.

	*Anzahl* has no spinner arrows (Marcel, 2026-10-09): typed, not stepped.

	*In den Warenkorb* adds the licence with its *Anzahl* and host to the
	basket ([[13-checkout]]), after the checks the server makes again
	([[PriceBasketRequest]]): a plugin needs its host, and a licence with a
	minimum takes no fewer.
--}}
<article {{ $attributes->class([
	'relative leading-[1.5] sm:text-lg sm:leading-[1.4] lg:text-xl',
	'mt-48 border-t border-black pt-8 first:mt-16 sm:mt-64 sm:pt-16 sm:first:mt-32' => ! $bare,
]) }}>
	{{-- **`bare` on the product's own page** ([[14-angebote]]): its name,
	     maker and description are the page's hero there, so the card starts
	     with the licences. Elsewhere the name links to that page. --}}
	@unless ($bare)
		<div class="flex flex-wrap items-baseline justify-between gap-x-16">
			<h3 class="font-bold text-teal">
				<a href="{{ \App\Support\SiteUrl::licence($product->getTranslation('slug', $locale)) }}" class="hover:text-black">{{ $product->getTranslation('title', $locale) }}</a>
			</h3>
			@if ($product->manufacturer)
				<div>von {{ $product->manufacturer->getTranslation('title', $locale) }}</div>
			@endif
		</div>

		@if ($html = $product->getTranslation('description', $locale, false))
			<x-ui.rich-text :html="$html" class="mt-16" />
		@endif
	@endunless

	@if ($product->three_years_on_request)
		<div class="mt-16"><em class="italic">3-Jahreslizenz auf Anfrage erhältlich</em></div>
	@endif

	@foreach ($licences as $licence)
		<div class="mt-16 border-t border-gray-400 pt-8 sm:mt-32 sm:grid sm:pt-16 sm:grid-cols-12 sm:items-start sm:gap-x-16 sm:gap-y-16 lg:gap-x-40"
			x-data="{
				quantity: {{ $licence['min'] }},
				host: '',
				add() {
					const quantity = parseInt(this.quantity, 10);

					if (@js($hosts->isNotEmpty()) && ! this.host) {
						return $store.toast.show('Bitte Hostsoftware wählen.', 'error');
					}

					if (! (quantity >= {{ $licence['min'] }})) {
						return $store.toast.show(@js('Mindestens '.$licence['min'].' Stück.'), 'error');
					}

					$store.basket.addLicence(@js($licence['uuid']), quantity, this.host || null);
				},
			}">
			{{-- Which licence, for what --}}
			<div class="sm:col-span-6 lg:col-span-5">
				<div>{{ $licence['label'] }}</div>
				@if ($licence['platforms'])
					<div class="text-gray-600">{{ $licence['platforms'] }}</div>
				@endif

				@if ($licence['note'])
					<div class="mt-8"><em class="italic">{{ $licence['note'] }}</em></div>
				@endif

				@if ($hosts->isNotEmpty())
					<div class="select-chevron relative mt-16 flex w-full items-center border-b border-black py-4">
						<select aria-label="Hostsoftware" x-model="host" class="block w-full cursor-pointer appearance-none bg-transparent pr-16 outline-hidden">
							<option value="">Hostsoftware wählen</option>
							@foreach ($hosts as $host)
								<option value="{{ $host }}">{{ $host }}</option>
							@endforeach
						</select>
					</div>
				@endif
			</div>

			{{-- What it costs --}}
			<div class="max-sm:mt-16 sm:col-span-6 lg:col-span-3">
				{{ $licence['price'] ?? 'kostenlos' }}
			</div>

			{{-- How many, and the way in --}}
			{{-- *X Stk.*, the unit after the field, and 32px to the button, 48
			     from `lg` (Marcel, 2026-10-20: was *Anzahl* before it, 16/24). --}}
			<div class="mt-16 flex items-center gap-32 sm:col-span-12 sm:mt-0 sm:justify-end lg:col-span-4 lg:gap-48">
				<label class="flex shrink-0 items-center gap-8">
					<input type="number" min="{{ $licence['min'] }}" x-model="quantity" @keydown.enter="add()" aria-label="Anzahl"
						class="w-56 [appearance:textfield] border-b border-black bg-transparent text-center outline-hidden focus:border-teal [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
					<span>Stk.</span>
				</label>

				<x-ui.button @click="add()" class="max-sm:grow">In den Warenkorb</x-ui.button>
			</div>
		</div>
	@endforeach
</article>
