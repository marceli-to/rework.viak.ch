@props(['makers', 'products', 'from'])

{{-- A software card's overview ([[card.software]]): maker, products, the cheapest licence net. --}}
<ul {{ $attributes }}>
	@if ($makers->isNotEmpty())
		<li class="py-4">{{ $makers->map(fn ($maker) => $maker->getTranslation('title', app()->getLocale()))->join(', ') }}</li>
	@endif
	<li class="py-4">{{ $products->count() }} {{ $products->count() === 1 ? 'Produkt' : 'Produkte' }}</li>
	@if ($from)
		<li class="py-4">ab CHF {{ number_format((float) $from, 2, '.', "'") }} exkl. {{ config('invoice.vat_label') }}</li>
	@endif
</ul>
