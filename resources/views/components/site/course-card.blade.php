@props(['course', 'eager' => false])

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
<article {{ $attributes->class(['border border-teal p-8 lg:p-16']) }}>
	<a href="{{ \App\Support\SiteUrl::course($course->getTranslation('slug', $locale)) }}" class="group block text-black">
		<header class="min-h-70 sm:min-h-90 lg:min-h-130">
			@if ($category)
				<div class="mb-4 text-xxs leading-none font-medium text-gray-600 sm:text-sm lg:text-lg">
					{{ $category->getTranslation('title', $locale) }}
				</div>
			@endif

			<h2 class="text-lg leading-[1.2] break-words text-teal sm:text-2xl lg:text-4xl">
				{{ $course->getTranslation('title', $locale) }}
			</h2>
		</header>

		<figure class="relative mt-8 block">
			{{-- The hover overlay, above the image in the source exactly as
			     legacy has it. --}}
			<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
				<h3 class="mb-8">Übersicht:</h3>
				<ul class="mb-16 sm:mb-24">
					@if ($expert)
						<li class="py-4">Experte: {{ $expert->first_name }} {{ $expert->last_name }}</li>
					@endif
					@if ($next)
						<li class="py-4">ab {{ $next->date->translatedFormat('j. F Y') }}</li>
					@endif
					@unless ($next?->free_of_charge)
						<li class="py-4">CHF {{ number_format((float) $course->fee, 2, '.', "'") }}</li>
					@endunless
				</ul>
				<div class="flex items-center gap-8">
					Weitere Informationen
					<x-icon.arrow-right class="size-16 shrink-0" />
				</div>
			</div>

			@if ($image = $course->teaser())
				{{-- Square, as legacy crops it: `/img/crop/…/1x1`. --}}
				<x-media.image
					:media="$image"
					ratio="1/1"
					sizes="(min-width: 1132px) 350px, (min-width: 700px) 45vw, 100vw"
					:max-width="1024"
					{{-- The first cards are the largest thing above the fold, so
					     lazy-loading them delays the metric they set. --}}
					:loading="$eager ? 'eager' : 'lazy'"
					class="block w-full"
				/>
			@else
				<div class="aspect-square w-full bg-gray-200"></div>
			@endif
		</figure>
	</a>
</article>
