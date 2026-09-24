@php
	$name = trim("{$expert->first_name} {$expert->last_name}");
	$visual = $expert->visuals()->first();
	$courses = $expert->eventsAsExpert->pluck('course')->unique('id');
@endphp

{{--
	`web/pages/experts/show.blade.php`, rebuilt 1:1 ([[09-public-site]]).

	**The course page's hero, with the column in bold.** The same
	`article.content-text-media` — a 16:9 visual, then a `span-4` aside against
	a `span-8` column, all teal — and the one difference is legacy's `is-course`
	modifier, which is what makes the course page's column regular weight. This
	page does not carry it, so the bio is bold. Measured against production on
	2026-09-24: 24px, 700, `#46baba`.

	Below it a `.content-list-item` of the courses the expert teaches, each an
	`icon-arrow-right:before` link. Legacy sets `og:image` from the visual, as
	the course page does from its own crop.
--}}
<x-layout.site
	:title="$name"
	heading="Experte"
	:image="$visual ? '/storage/uploads/'.$visual->file : null"
>
	{{-- `section.container`: 48px under it, 64px from `bp-md`, and none under
	     the last one on the page. --}}
	<article @class(['text-teal sm:flex sm:flex-col', 'mb-48 lg:mb-64' => $courses->isNotEmpty()])>
		<figure class="mb-24 sm:mb-32">
			@if ($visual)
				<x-media.image
					:media="$visual"
					ratio="16/9"
					sizes="(min-width: 1132px) 1068px, 100vw"
					:max-width="1600"
					:alt="$name"
					loading="eager"
					class="block w-full"
				/>
			@else
				<div class="aspect-video w-full bg-gray-200"></div>
			@endif
		</figure>

		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="mb-12 sm:col-span-4">
				<h1 class="font-bold">{{ $name }}</h1>
				@if ($expert->expertProfile?->title)
					<h2>{{ $expert->expertProfile->title }}</h2>
				@endif
			</div>

			@if ($expert->expertProfile?->description)
				<x-site.rich-text :html="$expert->expertProfile->description" hero class="font-bold sm:col-span-8" />
			@endif
		</div>
	</article>

	@if ($courses->isNotEmpty())
		{{-- `.content-list-item`: the grey rule and label the course page's
		     *Weitere Kurse* also uses, and here legacy's `mb-6x md:mb-8x` under
		     the label. --}}
		<div class="mb-64 border-t border-gray-400 pt-8 sm:border-t-2 sm:pt-16 sm:text-lg lg:text-xl">
			<h2 class="mb-24 text-md leading-none font-bold text-gray-400 sm:text-lg lg:mb-32 lg:text-xl">Kurse</h2>

			@foreach ($courses as $course)
				{{-- `icon-arrow-right:before`: the arrow first, the title 8px
				     after it (12px from sm), teal on hover. The arrow's own
				     stylesheet says `ease-in` and `%link` wins with `ease-out` —
				     production computes the second. --}}
				<a href="{{ \App\Support\SiteUrl::course($course->getTranslation('slug', app()->getLocale())) }}"
					title="{{ $course->title }}"
					class="mb-8 flex items-center text-black transition-colors duration-100 ease-out hover:text-teal sm:mb-16">
					<x-icon.arrow-right class="shrink-0" />
					<span class="ml-8 block sm:ml-12">{{ $course->title }}</span>
				</a>
			@endforeach
		</div>
	@endif
</x-layout.site>
