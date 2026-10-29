@props(['product', 'eager' => false])

@php
	$locale = app()->getLocale();
	$software = $product->software;
	$category = $product->categories->sortBy('order')->first();
	$from = $product->fromPrice();
@endphp

{{--
	A licence product on the Software list ([[14-angebote]]): the software
	card (`card/software.blade.php`) one level down, the same border, header,
	square image and hover, leading to the product's page. A product has no
	picture of its own; it shows its software's. The overlay names the maker,
	the Angebot it belongs to and the cheapest licence, net.
--}}
<article {{ $attributes->class(['border border-teal p-8 lg:p-16']) }}>
	<a href="{{ \App\Support\SiteUrl::licence($product->getTranslation('slug', $locale)) }}" class="group block text-black">
		<header class="min-h-70 sm:min-h-90 lg:min-h-130">
			@if ($category)
				<div class="mb-4 text-xxs leading-none font-medium text-gray-600 sm:text-sm lg:text-lg">
					{{ $category->getTranslation('title', $locale) }}
				</div>
			@endif

			<h2 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-2xl lg:text-4xl">
				{{ $product->getTranslation('title', $locale) }}
			</h2>
		</header>

		<figure class="relative mt-8 block">
			<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
				<h3 class="mb-8">Übersicht:</h3>
				<ul class="mb-16 sm:mb-24">
					@if ($product->manufacturer)
						<li class="py-4">{{ $product->manufacturer->getTranslation('title', $locale) }}</li>
					@endif
					@if ($software)
						<li class="py-4">für {{ $software->getTranslation('title', $locale) }}</li>
					@endif
					@if ($from)
						<li class="py-4">ab CHF {{ number_format((float) $from, 2, '.', "'") }} exkl. {{ config('invoice.vat_label') }}</li>
					@endif
				</ul>
				<div class="flex items-center gap-8">
					Lizenz kaufen
					<x-icon.arrow-right class="shrink-0" />
				</div>
			</div>

			@if ($image = $software?->teaser())
				<x-media.image
					:media="$image"
					ratio="1/1"
					sizes="(min-width: 1132px) 350px, (min-width: 700px) 45vw, 100vw"
					:max-width="1024"
					:loading="$eager ? 'eager' : 'lazy'"
					class="block w-full"
				/>
			@else
				<div class="aspect-square w-full bg-gray-200"></div>
			@endif
		</figure>
	</a>
</article>
