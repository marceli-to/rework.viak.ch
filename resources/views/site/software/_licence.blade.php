@php
	$locale = app()->getLocale();
	$from = $product->fromPrice();
	$features = $product->featureList($locale);
	$href = \App\Support\SiteUrl::licence($product->getTranslation('slug', $locale));
@endphp

{{--
	One licence product on an Angebot's page ([[14-angebote]], after
	`Rhino.html`): its name and *ab* its cheapest listed licence, its features
	as ticks, and the way to its own page, where it is bought (the client,
	2026-10-29). Drawn as the course row beside it (`_course.blade.php`).
--}}
<article class="mt-16 border-t border-black pt-8 sm:mt-32 sm:pt-16">
	<div class="flex flex-wrap items-baseline justify-between gap-x-16">
		<h3 class="font-bold text-teal">
			<a href="{{ $href }}" class="hover:text-black">{{ $product->getTranslation('title', $locale) }}</a>
		</h3>
		@if ($from)
			<div class="shrink-0">ab CHF {{ number_format((float) $from, 2, '.', "'") }}</div>
		@endif
	</div>

	@if ($features !== [])
		<ul class="mt-8 flex flex-wrap gap-x-24 gap-y-4">
			@foreach ($features as $feature)
				<li class="flex items-baseline gap-8">
					<x-icon.checkmark class="w-14 shrink-0 text-teal" />
					{{ $feature }}
				</li>
			@endforeach
		</ul>
	@elseif ($product->manufacturer)
		<div class="mt-8">von {{ $product->manufacturer->getTranslation('title', $locale) }}</div>
	@endif

	<div class="mt-16 flex sm:justify-end">
		<x-ui.button :href="$href" variant="outline" class="max-sm:grow">Lizenz kaufen</x-ui.button>
	</div>
</article>
