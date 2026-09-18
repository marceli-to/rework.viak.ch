@props(['course'])

@php
	$locale = app()->getLocale();
	$next = $course->events->first();
	$expert = $next?->experts->first();
	$category = $course->categories->first();
@endphp

{{--
	`components/cards/_teaser.scss`, rebuilt 1:1.

	A 1px teal border, a header of fixed minimum height so the cards line up
	however long the titles are, a category label above a teal heading, and a
	square image with an overlay that fades in on hover carrying the details.
	The overlay is desktop only — legacy hides it below 700px, where there is no
	hover to reveal it with.
--}}
<article {{ $attributes->class(['border border-teal p-2 md:p-4']) }}>
	<a href="{{ \App\Support\SiteUrl::course($course->getTranslation('slug', $locale)) }}" class="group block text-ink">
		<header class="min-h-[70px] sm:min-h-[90px] md:min-h-[130px]">
			@if ($category)
				<div class="mb-1 text-[10px] leading-none font-medium text-muted sm:text-[13px] md:text-[16px]">
					{{ $category->getTranslation('title', $locale) }}
				</div>
			@endif

			<h2 class="text-[16px] leading-[1.2] break-words text-teal sm:text-[20px] md:text-[28px]">
				{{ $course->getTranslation('title', $locale) }}
			</h2>
		</header>

		<figure class="relative mt-2 block">
			{{-- The hover overlay, above the image in the source exactly as
			     legacy has it. --}}
			<div class="absolute hidden h-full w-full bg-teal p-2 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-[14px] md:p-4 md:text-[18px]">
				<h3 class="mb-2">Übersicht:</h3>
				<ul class="mb-4 sm:mb-6">
					@if ($expert)
						<li class="py-1">Experte: {{ $expert->first_name }} {{ $expert->last_name }}</li>
					@endif
					@if ($next)
						<li class="py-1">ab {{ $next->date->translatedFormat('j. F Y') }}</li>
					@endif
					@unless ($next?->free_of_charge)
						<li class="py-1">CHF {{ number_format((float) $course->fee, 2, '.', "'") }}</li>
					@endunless
				</ul>
				<div>Weitere Informationen →</div>
			</div>

			@if ($image = $course->teaser())
				{{-- Square, as legacy crops it: `/img/crop/…/1x1`. --}}
				<x-media.image
					:media="$image"
					ratio="1/1"
					sizes="(min-width: 1132px) 350px, (min-width: 700px) 45vw, 100vw"
					:max-width="1024"
					class="block w-full"
				/>
			@else
				<div class="aspect-square w-full bg-light"></div>
			@endif
		</figure>
	</a>
</article>
