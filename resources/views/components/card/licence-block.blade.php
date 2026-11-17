@props(['product', 'variant', 'anchor' => false])

@php
	$locale = app()->getLocale();
	// The title reads the product; the page has it loaded already.
	$variant->setRelation('product', $product);
	$hosts = collect($product->hostNames());
	$min = max(1, (int) $variant->min_quantity);
	$price = (float) $variant->price > 0 ? 'CHF '.number_format((float) $variant->price, 2, '.', "'") : null;
	$compare = $variant->compare_price ? 'CHF '.number_format((float) $variant->compare_price, 2, '.', "'") : null;
	$note = $variant->getTranslation('note', $locale, false) ?: null;
	$terms = collect([$variant->blockTerms(), $variant->platforms?->map->label()->join(', ')])->filter();
@endphp

{{--
	**One licence, one block** on an Angebot's licence page (#71, built flat
	2026-11-17), as the client's docs draw it (`../Software/Corona`): the
	title, a text under it, the price, the button. Until then a product was
	the heading and its licences rows under it; the docs have no product
	level, so neither has the page now.

	- **Title**: as the docs write it, *Corona Solo (named)*, *Corona Render
	  Node 1 Stück (floating)* ([[LicenceVariant::blockTitle]]), an `<h3>` in
	  teal under the collapsible's `<h2>`. Under it in grey what kind of
	  licence (*Jahresmietlizenz, Einzelplatz*) and the platforms.
	- **Text**: the licence's *Hinweis*, a paragraph with its line breaks
	  (*"manchmal muss man zu einer Lizenz einen Hinweis schreiben können"*),
	  and *3-Jahreslizenz auf Anfrage erhältlich* where its product has one.
	- **Price** with its Streichpreis, ***Anzahl*** and ***In den Warenkorb***
	  as before: from `lg` the row is `5 / 3 / 4`, *Anzahl* typed, no
	  spinner (Marcel, 2026-10-09), *X Stk.* after it (2026-10-20).

	Drawn as *Aktuelle Kurse* (`card/event.blade.php`): its black rule,
	spacing and type. The maker and the product's description went with the
	product heading: the docs have neither, and the one description was filler.

	`anchor`: a product's first block carries its slug as `id`, where its old
	page and every tile and row that names it lead ([[SiteUrl::licence]]).

	*In den Warenkorb* adds the licence with its *Anzahl* and host to the
	basket ([[13-checkout]]), after the checks the server makes again
	([[PriceBasketRequest]]): a plugin needs its host, and a licence with a
	minimum takes no fewer.
--}}
<article @if ($anchor) id="{{ $product->getTranslation('slug', $locale) }}" @endif
	class="relative mt-16 border-t border-black pt-8 leading-[1.5] first:mt-16 sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl"
	x-data="{
		quantity: {{ $min }},
		host: '',
		add() {
			const quantity = parseInt(this.quantity, 10);

			if (@js($hosts->isNotEmpty()) && ! this.host) {
				return $store.toast.show('Bitte Hostsoftware wählen.', 'error');
			}

			if (! (quantity >= {{ $min }})) {
				return $store.toast.show(@js('Mindestens '.$min.' Stück.'), 'error');
			}

			$store.basket.addLicence(@js($variant->uuid), quantity, this.host || null);
		},
	}">
	<h3 class="font-bold text-teal">{{ $variant->blockTitle() }}</h3>

	<div class="sm:mt-8 sm:grid sm:grid-cols-12 sm:items-start sm:gap-x-16 sm:gap-y-16 lg:gap-x-40">
		{{-- What kind, and the text --}}
		<div class="sm:col-span-6 lg:col-span-5">
			@foreach ($terms as $line)
				<div class="text-gray-600">{{ $line }}</div>
			@endforeach

			@if ($note)
				<p class="mt-8 whitespace-pre-line">{{ $note }}</p>
			@endif

			@if ($product->three_years_on_request)
				<p class="mt-8"><em class="italic">3-Jahreslizenz auf Anfrage erhältlich</em></p>
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

		{{-- What it costs; a demo is *kostenlos* (#35) --}}
		<div class="max-sm:mt-16 sm:col-span-6 lg:col-span-3">
			{{ $price ?? 'kostenlos' }}
			@if ($compare)
				<s class="text-gray-600"><span class="sr-only">statt </span>{{ $compare }}</s>
			@endif
		</div>

		{{-- How many, and the way in --}}
		<div class="mt-16 flex items-center gap-32 sm:col-span-12 sm:mt-0 sm:justify-end lg:col-span-4 lg:gap-48">
			<label class="flex shrink-0 items-center gap-8">
				<input type="number" min="{{ $min }}" x-model="quantity" @keydown.enter="add()" aria-label="Anzahl"
					class="w-56 [appearance:textfield] border-b border-black bg-transparent text-center outline-hidden focus:border-teal [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
				<span>Stk.</span>
			</label>

			<x-ui.button @click="add()" class="max-sm:grow">In den Warenkorb</x-ui.button>
		</div>
	</div>
</article>
