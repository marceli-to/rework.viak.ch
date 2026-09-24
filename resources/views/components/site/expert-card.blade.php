@props(['expert', 'eager' => false])

@php
	$name = trim("{$expert->first_name} {$expert->last_name}");
	$courses = $expert->eventsAsExpert->pluck('course')->unique('id');
	$teaser = $expert->media->firstWhere('is_teaser', true);
@endphp

{{--
	`web/pages/experts/components/card.blade.php` — the same
	`components/cards/_teaser.scss` card as the course list, with the name for a
	heading, no category above it, and the courses they teach in the overlay.
	Measured against production on 2026-09-24.

	**The teaser is the flagged image or nothing**, not `teaser()`, which falls
	back to the first image. Legacy's `teaserImage` is `type = 'teaser'` exactly,
	and an expert without one gets the grey placeholder rather than a crop of
	their 16:9 visual.
--}}
<article {{ $attributes->class(['border border-teal p-8 lg:p-16']) }}>
	<a href="{{ \App\Support\SiteUrl::expert($expert) }}" class="group block text-black">
		<header class="min-h-70 sm:min-h-90 lg:min-h-130">
			<h2 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-2xl lg:text-4xl">
				{{ $name }}
			</h2>
		</header>

		<figure class="relative mt-8 block">
			<div class="absolute hidden h-full w-full bg-teal p-8 leading-[1.2] font-bold text-white opacity-0 transition-opacity duration-[120ms] ease-in-out group-hover:opacity-100 sm:block sm:text-md lg:p-16 lg:text-xl">
				@if ($courses->isNotEmpty())
					<h3 class="mb-8">Kurse:</h3>
					{{-- `.card-teaser__list` carries the margin, and the list is
					     `%unordered-list`: a disc outside, hung on a 20px margin
					     on the item rather than padding on the list. --}}
					<div class="mb-16 sm:mb-24">
						<ul class="list-disc">
							@foreach ($courses as $course)
								<li class="ml-20 py-4">{{ $course->title }}</li>
							@endforeach
						</ul>
					</div>
				@endif

				{{-- `icon-arrow-right:after` is `space-between`: the arrow sits
				     on the far edge of the overlay, not beside the words. --}}
				<div class="flex items-center justify-between">
					Weitere Informationen
					<x-icon.arrow-right class="shrink-0" />
				</div>
			</div>

			@if ($teaser)
				<x-media.image
					:media="$teaser"
					ratio="1/1"
					sizes="(min-width: 1132px) 330px, (min-width: 700px) 30vw, 50vw"
					:max-width="1024"
					:alt="$name"
					:loading="$eager ? 'eager' : 'lazy'"
					class="block w-full"
				/>
			@else
				<div class="aspect-square w-full bg-gray-200"></div>
			@endif
		</figure>
	</a>
</article>
