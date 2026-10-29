@php
	$locale = app()->getLocale();
	$from = $product->fromPrice();
	$features = $product->featureList($locale);
	$href = \App\Support\SiteUrl::licence($product->getTranslation('slug', $locale));
@endphp

{{--
	One licence product on an Angebot's page ([[14-angebote]]), drawn as the
	course row above it (`_course.blade.php`), so as a date row on the course
	page: the product and its maker; its features as ticks; *ab* its cheapest
	listed licence and the way to its own page, where it is bought (the
	client, 2026-10-29).
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		{{-- Which product, by whom --}}
		<div class="sm:col-span-4">
			<a href="{{ $href }}" class="font-bold text-teal hover:text-black">{{ $product->getTranslation('title', $locale) }}</a>
			@if ($product->manufacturer)
				<div>von {{ $product->manufacturer->getTranslation('title', $locale) }}</div>
			@endif
		</div>

		{{-- What it is --}}
		<div class="max-sm:mt-8 sm:col-span-4">
			@if ($features !== [])
				<ul>
					@foreach ($features as $feature)
						<li class="flex items-baseline gap-8">
							<x-icon.checkmark class="w-14 shrink-0 text-teal" />
							{{ $feature }}
						</li>
					@endforeach
				</ul>
			@endif
		</div>

		{{-- What it costs, and the way on --}}
		<div class="mt-16 flex items-start justify-between gap-16 sm:col-span-4 sm:mt-0">
			<div class="shrink-0 whitespace-nowrap">
				@if ($from)
					ab CHF {{ number_format((float) $from, 2, '.', "'") }}
				@endif
			</div>

			<x-ui.button :href="$href" variant="outline">Lizenz kaufen</x-ui.button>
		</div>
	</div>
</article>
