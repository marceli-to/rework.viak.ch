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
	One product in a software page's *Lizenzen* ([[05-licences]]), drawn as an
	event is in *Aktuelle Kurse* (`card/event.blade.php`): the same rule,
	spacing and type. From `lg` four columns, `2 / 4 / 2 / 4` (Marcel,
	2026-10-09): what it is, which licence, the platforms, then the price
	beside *Anzahl* over the button. The last is `span-4` because price and
	button sit side by side, as an event's fee and *Buchen* do; the licences
	keep `span-4` because their names are the long ones, and a product name
	may wrap instead.

	Between `sm` and `lg` that is too narrow, so it is the event's three
	`span-4`: the platforms go under the licences and the price, *Anzahl* and
	button stack. The platforms are printed twice for it, one copy per
	breakpoint, as the event card prints its fee.

	**The licences are a list, not a `<select>`.** Their names run long
	(*Bundle mit Neulizenz, Dauerlizenz, Netzwerk (floating)*), so a closed
	select needed `span-8` to show one, and hid the rest. Listed, they wrap
	the way an event's days stack, behind the site's checkbox square
	(`form/checkbox.blade.php`) as a radio. At most seven, on one product; a
	product with one licence just says it.

	*exkl. MWST* sits under each price, an italic remark as the event's state
	line is, where it was one line under the whole list ([[05-licences]]).

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
		{{-- What it is --}}
		<div class="sm:col-span-4 lg:col-span-2">
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

		{{-- Which licence --}}
		<div class="max-sm:mt-16 sm:col-span-4">
			@if ($licences->count() > 1)
				<fieldset>
					<legend class="sr-only">Lizenz</legend>
					@foreach ($licences as $variant)
						{{-- The square's offsets are the checkbox's, on the
						     row's 1.5 / 1.4: half the line less half the box. --}}
						<label class="flex cursor-pointer items-start gap-8 hover:text-teal sm:gap-12">
							<input type="radio" name="licence-{{ $product->uuid }}" value="{{ $variant->uuid }}"
								x-model="picked" @change="pick()" @checked($loop->first)
								class="mt-4 size-12 shrink-0 cursor-pointer appearance-none border border-black bg-white outline-hidden checked:border-teal checked:bg-teal focus-visible:ring-1 focus-visible:ring-teal focus-visible:ring-offset-2 sm:mt-4 sm:size-14 lg:mt-5">
							<span>{{ $variant->shopLabel() }}</span>
						</label>
					@endforeach
				</fieldset>
			@else
				<div>{{ $licences->first()?->shopLabel() }}</div>
			@endif

			{{-- Below `lg` the platforms go here; from `lg` they are a column. --}}
			<div class="text-gray-600 lg:hidden" :class="choice?.platforms || 'hidden'" x-text="choice?.platforms">{{ $first['platforms'] ?? '' }}</div>

			@if ($hosts->isNotEmpty())
				<div class="select-chevron relative mt-16 flex w-full items-center border-b border-black py-4">
					<select aria-label="Hostsoftware" class="block w-full cursor-pointer appearance-none bg-transparent pr-16 outline-hidden">
						<option value="">Hostsoftware wählen</option>
						@foreach ($hosts as $host)
							<option value="{{ $host }}">{{ $host }}</option>
						@endforeach
					</select>
				</div>
			@endif

			<template x-if="choice?.note">
				<div class="mt-16"><em class="italic" x-text="choice.note"></em></div>
			</template>
		</div>

		{{-- For what --}}
		<div class="text-gray-600 max-lg:hidden lg:col-span-2" x-text="choice?.platforms">{{ $first['platforms'] ?? '' }}</div>

		{{-- What it costs, how many, and the way in --}}
		<div class="max-sm:mt-16 sm:col-span-4 lg:flex lg:items-start lg:justify-between">
			<div class="lg:mr-32">
				<div class="whitespace-nowrap" x-text="choice?.price">{{ $first['price'] ?? '' }}</div>
				<div x-show="choice?.price !== 'kostenlos'"><em class="italic">exkl. MWST</em></div>
			</div>

			<div class="max-lg:mt-16 lg:flex lg:flex-col lg:items-end">
				<label class="flex items-center gap-8">
					<span>Anzahl</span>
					<input type="number" x-model.number="quantity" :min="choice?.min ?? 1" value="{{ $first['min'] ?? 1 }}"
						class="w-56 border-b border-black bg-transparent text-center outline-hidden focus:border-teal">
				</label>

				<x-ui.button disabled title="Folgt mit dem Checkout" class="mt-16 cursor-not-allowed opacity-40">In den Warenkorb</x-ui.button>
			</div>
		</div>
	</div>
</article>
