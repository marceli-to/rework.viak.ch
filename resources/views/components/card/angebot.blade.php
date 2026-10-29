@props(['software', 'eager' => false])

@php
	$locale = app()->getLocale();
	$chf = fn (string $amount) => 'CHF '.number_format((float) $amount, 2, '.', "'");
	$category = $software->categories->sortBy('order')->first();
	$lead = $software->getTranslation('subtitle', $locale, false);
	$course = $software->courseFrom();
	$licence = $software->licenceFrom();
	$note = $software->getTranslation('licence_note', $locale, false);
	$courses = $software->courses->filter(fn ($item) => $item->publish);
	$hasCourses = $courses->isNotEmpty();
	// A software only courses use often has no picture of its own (Blender,
	// After Effects): its first course's stands in, as the mockup's After
	// Effects tile shows a course.
	$image = $software->teaser() ?? $courses->sortBy('order')->map->teaser()->filter()->first();
@endphp

{{--
	One Angebot under *Beliebte Angebote* ([[14-angebote]]): the mockup's
	paired tile (`Homepage.html`, markers 6 and 7) in the course card's frame,
	a 1px teal border, the category over a teal title, the square picture,
	then the lead and, under a grey rule, the two prices side by side.

	**Kurs ab** by the hybrid of #52 ([[Software::courseFrom]]): the bookable
	price, else the course fee with *Daten auf Anfrage*, else *Kurs auf
	Anfrage*; nothing on an Angebot without courses. **Lizenz ab** its
	cheapest listed licence, else the software's *Lizenz-Hinweis* (*Lizenz
	direkt bei Adobe*), else nothing. Net, as the Software list says them;
	courses carry no VAT. The prices keep to the foot of the tile, so a row
	of tiles lines them up.
--}}
<article {{ $attributes->class(['flex flex-col border border-teal p-8 lg:p-16']) }}>
	<a href="{{ \App\Support\SiteUrl::software($software->getTranslation('slug', $locale)) }}" class="group flex flex-1 flex-col text-black">
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

		<figure class="mt-8 block">
			@if ($image)
				<x-media.image
					:media="$image"
					ratio="1/1"
					sizes="(min-width: 1132px) 350px, (min-width: 700px) 30vw, 50vw"
					:max-width="1024"
					:loading="$eager ? 'eager' : 'lazy'"
					class="block w-full"
				/>
			@else
				<div class="aspect-square w-full bg-gray-200"></div>
			@endif
		</figure>

		<div class="mt-16 flex flex-1 flex-col gap-16 text-md leading-[1.3] lg:text-lg">
			@if ($lead)
				<p class="text-gray-600">{{ $lead }}</p>
			@endif

			<div class="mt-auto flex flex-wrap justify-between gap-x-16 gap-y-4 border-t border-gray-400 pt-8 font-bold text-teal transition-colors group-hover:text-black">
				@if ($course)
					<span>Kurs ab {{ $chf($course['price']) }}@unless ($course['dated'])<span class="font-normal">, Daten auf Anfrage</span>@endunless</span>
				@elseif ($hasCourses)
					<span>Kurs auf Anfrage</span>
				@endif

				@if ($licence)
					<span>Lizenz ab {{ $chf($licence) }}</span>
				@elseif ($note)
					<span>{{ $note }}</span>
				@endif
			</div>
		</div>
	</a>
</article>
