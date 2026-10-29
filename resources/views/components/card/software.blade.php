@props(['software', 'eager' => false, 'kind' => null, 'plain' => false])

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
<article {{ $attributes->class(['border border-teal p-8 lg:p-16', 'flex flex-col' => $plain]) }}>
	<a href="{{ \App\Support\SiteUrl::software($software->getTranslation('slug', $locale)) }}" @class(['group text-black', 'flex flex-1 flex-col' => $plain, 'block' => ! $plain])>
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

		{{-- **`plain`: no picture** (*Das könnte dich auch interessieren*,
		     Marcel 2026-10-20). The overview the hover shows, as text under the
		     title, and the way on at the foot. --}}
		@if ($plain)
			<div class="flex flex-1 flex-col justify-between gap-16 text-md leading-[1.3] lg:text-lg">
				<ul>
					@if ($makers->isNotEmpty())
						<li>{{ $makers->map(fn ($maker) => $maker->getTranslation('title', $locale))->join(', ') }}</li>
					@endif
					{{-- None on an Angebot of courses only ([[14-angebote]]). --}}
					@if ($products->isNotEmpty())
						<li>{{ $products->count() }} {{ $products->count() === 1 ? 'Produkt' : 'Produkte' }}</li>
					@endif
					@if ($from)
						<li>ab CHF {{ number_format((float) $from, 2, '.', "'") }} exkl. {{ config('invoice.vat_label') }}</li>
					@endif
				</ul>
				<div class="flex items-center gap-8 transition-colors group-hover:text-teal">
					Weitere Informationen
					<x-icon.arrow-right class="shrink-0" />
				</div>
			</div>
		@else
			<figure class="relative mt-8 block">
				<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
					<h3 class="mb-8">Übersicht:</h3>
					<ul class="mb-16 sm:mb-24">
						@if ($makers->isNotEmpty())
							<li class="py-4">{{ $makers->map(fn ($maker) => $maker->getTranslation('title', $locale))->join(', ') }}</li>
						@endif
						@if ($products->isNotEmpty())
							<li class="py-4">{{ $products->count() }} {{ $products->count() === 1 ? 'Produkt' : 'Produkte' }}</li>
						@endif
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
		@endif
	</a>
</article>
