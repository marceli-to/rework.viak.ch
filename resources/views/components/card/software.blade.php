@props(['software', 'eager' => false])

@php
	$locale = app()->getLocale();
	$category = $software->categories->first();
	$products = $software->products->filter->isListed();
	$makers = $products->pluck('manufacturer')->filter()->unique('id');
	$from = $products->map->fromPrice()->filter()->sortBy(fn ($price) => (float) $price)->first();
@endphp

{{--
	A software on the list ([[05-licences]]): the course card
	(`card/course.blade.php`) with the software's facts in the overlay, the
	maker, how many products and the cheapest licence, net. Same border,
	header, square image and hover.
--}}
<article {{ $attributes->class(['border border-teal p-8 lg:p-16']) }}>
	<a href="{{ \App\Support\SiteUrl::software($software->getTranslation('slug', $locale)) }}" class="group block text-black">
		<header class="min-h-70 sm:min-h-90 lg:min-h-130">
			@if ($category)
				<div class="mb-4 text-xxs leading-none font-medium text-gray-600 sm:text-sm lg:text-lg">
					{{ $category->getTranslation('title', $locale) }}
				</div>
			@endif

			<h2 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-2xl lg:text-4xl">
				{{ $software->getTranslation('title', $locale) }}
			</h2>
		</header>

		<figure class="relative mt-8 block">
			<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
				<h3 class="mb-8">Übersicht:</h3>
				<ul class="mb-16 sm:mb-24">
					@if ($makers->isNotEmpty())
						<li class="py-4">{{ $makers->map(fn ($maker) => $maker->getTranslation('title', $locale))->join(', ') }}</li>
					@endif
					<li class="py-4">{{ $products->count() }} {{ $products->count() === 1 ? 'Produkt' : 'Produkte' }}</li>
					@if ($from)
						<li class="py-4">ab CHF {{ number_format((float) $from, 2, '.', "'") }} exkl. {{ config('invoice.vat_label') }}</li>
					@endif
				</ul>
				<div class="flex items-center gap-8">
					Weitere Informationen
					<x-icon.arrow-right class="shrink-0" />
				</div>
			</div>

			@if ($image = $software->teaser())
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
