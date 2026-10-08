@props(['product'])

@php
	$locale = app()->getLocale();
	$licences = $product->variants->filter(fn ($variant) => $variant->listed)->values();
	$hosts = collect($product->hosts ?? [])->filter()->values();

	// What the row's Alpine reads: the licence picked decides price, minimum and note.
	$choices = $licences->map(fn ($variant) => [
		'uuid' => $variant->uuid,
		// A demo costs nothing (#35): *kostenlos*, as a free course date says it.
		'price' => (float) $variant->price > 0 ? 'CHF '.number_format((float) $variant->price, 2, '.', "'") : 'kostenlos',
		'min' => max(1, (int) $variant->min_quantity),
		'note' => $variant->getTranslation('note', $locale, false) ?: null,
		'platforms' => $variant->platforms?->map->label()->join(', '),
	])->values();
	$first = $choices->first();
@endphp

{{--
	One product in a software page's *Lizenzen* ([[05-licences]]), the row an
	event is in *Aktuelle Kurse* (`card/event.blade.php`): the same rule,
	spacing and type. Two columns rather than its three: the licence names run
	long (*Vollversion, Dauerlizenz, Einzelplatz oder Netzwerk*), so the
	product, its maker and description take `span-4` and the licence picked,
	how many, the price and the button `span-8`.

	**The button waits for the checkout** (chunk 13, [[13-checkout]]): the
	basket holds courses only and needs a login, so it is drawn and does
	nothing yet (Marcel, 2026-10-08).
--}}
<article
	{{ $attributes->class(['relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl']) }}
	x-data="{ choices: @js($choices), picked: @js($first['uuid'] ?? ''), quantity: @js($first['min'] ?? 1),
		get choice() { return this.choices.find((choice) => choice.uuid === this.picked) ?? this.choices[0] },
		pick() { this.quantity = Math.max(this.quantity, this.choice.min) } }"
>
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<div class="sm:col-span-4">
			<strong class="font-bold">{{ $product->getTranslation('title', $locale) }}</strong>
			@if ($product->manufacturer)
				<div>von {{ $product->manufacturer->getTranslation('title', $locale) }}</div>
			@endif

			@if ($html = $product->getTranslation('description', $locale, false))
				<x-ui.rich-text :html="$html" class="mt-16" />
			@endif

			@if ($product->three_years_on_request)
				<div class="mt-16"><em class="italic">3-Jahreslizenz auf Anfrage erhältlich</em></div>
			@endif
		</div>

		<div class="max-sm:mt-16 sm:col-span-8">
			<div class="select-chevron relative flex w-full items-center border-b border-black py-4">
				<select x-model="picked" @change="pick()" aria-label="Lizenz"
					class="block w-full cursor-pointer appearance-none bg-transparent pr-16 font-bold outline-hidden">
					@foreach ($licences as $variant)
						<option value="{{ $variant->uuid }}">{{ $variant->shopLabel() }}</option>
					@endforeach
				</select>
			</div>

			@if ($hosts->isNotEmpty())
				<div class="select-chevron relative mt-8 flex w-full items-center border-b border-black py-4">
					<select aria-label="Hostsoftware" class="block w-full cursor-pointer appearance-none bg-transparent pr-16 outline-hidden">
						<option value="">Hostsoftware wählen</option>
						@foreach ($hosts as $host)
							<option value="{{ $host }}">{{ $host }}</option>
						@endforeach
					</select>
				</div>
			@endif

			<div class="mt-16 flex flex-wrap items-center gap-x-32 gap-y-16">
				<span class="grow max-sm:basis-full" x-text="choice?.platforms">{{ $first['platforms'] ?? '' }}</span>

				<label class="flex items-center gap-8">
					<span class="sr-only">Anzahl</span>
					<input type="number" x-model.number="quantity" :min="choice?.min ?? 1" value="{{ $first['min'] ?? 1 }}"
						class="w-56 border-b border-black bg-transparent py-4 text-center outline-hidden">
					<span>Stück</span>
				</label>

				<span class="max-sm:ml-auto" x-text="choice?.price">{{ $first['price'] ?? '' }}</span>

				<x-ui.button disabled title="Folgt mit dem Checkout" class="cursor-not-allowed opacity-40 max-sm:w-full">In den Warenkorb</x-ui.button>
			</div>

			<template x-if="choice?.note">
				<div class="mt-8"><em class="italic" x-text="choice.note"></em></div>
			</template>
		</div>
	</div>
</article>
