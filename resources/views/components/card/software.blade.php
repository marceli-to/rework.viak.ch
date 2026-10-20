@props(['software', 'eager' => false, 'kind' => null])

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
			{{-- *Kurs* or *Software* in the top right, where the two share a
			     grid (*Beliebte Angebote*, Marcel 2026-10-09); a list of one
			     kind passes nothing and keeps the category alone. --}}
			@if ($category || $kind)
				<div class="mb-4 flex items-start justify-between gap-8 text-xxs leading-none font-medium sm:text-sm lg:text-lg">
					<span class="text-gray-600">{{ $category?->getTranslation('title', $locale) }}</span>
					@if ($kind)
						<span class="shrink-0 bg-teal px-4 py-2 font-bold text-white">{{ $kind }}</span>
					@endif
				</div>
			@endif

			<h2 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-2xl lg:text-4xl">
				{{ $software->getTranslation('title', $locale) }}
			</h2>
		</header>

		{{--
			**Without an image, the card carries its facts instead** (Marcel,
			2026-10-20): the overview the hover shows over a picture, in the
			picture's square, so the card keeps its place in a grid of cards
			with images. Most software has no image yet.
		--}}
		@if ($image = $software->teaser())
			<figure class="relative mt-8 block">
				<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
					<h3 class="mb-8">Übersicht:</h3>
					<x-software.facts :makers="$makers" :products="$products" :from="$from" class="mb-16 sm:mb-24" />
					<div class="flex items-center gap-8">
						Weitere Informationen
						<x-icon.arrow-right class="shrink-0" />
					</div>
				</div>

				<x-media.image
					:media="$image"
					ratio="1/1"
					sizes="(min-width: 1132px) 350px, (min-width: 700px) 45vw, 100vw"
					:max-width="1024"
					:loading="$eager ? 'eager' : 'lazy'"
					class="block w-full"
				/>
			</figure>
		@else
			<div class="mt-8 flex aspect-square w-full flex-col justify-between border-t border-teal pt-8 leading-[1.2] font-bold sm:text-md lg:pt-16 lg:text-xl">
				<div>
					<h3 class="mb-8">Übersicht:</h3>
					<x-software.facts :makers="$makers" :products="$products" :from="$from" />
				</div>
				<div class="flex items-center gap-8 transition-colors group-hover:text-teal">
					Weitere Informationen
					<x-icon.arrow-right class="shrink-0" />
				</div>
			</div>
		@endif
	</a>
</article>
