@php
	$locale = app()->getLocale();
	$title = $software->getTranslation('title', $locale);
	$subtitle = $software->getTranslation('subtitle', $locale, false);
	$visuals = $software->visuals();
	$information = $software->getTranslation('information', $locale, false);
	$og = $software->openGraph();
@endphp

{{--
	One Angebot ([[14-angebote]]), the overview the client asked for on
	2026-10-26 and settled on 2026-10-29: **nothing is bought here**, every
	row leads to its product's page. The mockup's content (`Rhino.html`) in
	the site's look, which is the course page's (`site/courses/show.blade.php`):
	the teal hero, then the collapsibles.

	- **The hero** as the course page has it. Not built: the mockup's
	  Vorhaben chips (built, then taken out by Marcel on 2026-10-29), its
	  anchor links (the collapsibles are a short scroll) and its breadcrumb
	  (the site has none).
	- **Kurse, then Lizenzen**, each a full-width collapsible whose rows are
	  drawn as the course page's date rows (Marcel, 2026-10-29: the two
	  columns side by side of the first version were too cramped). A course
	  row (`_course.blade.php`) links to the course page, a licence row
	  (`_licence.blade.php`) to the licence's. Under the courses the way to a
	  Firmenschulung; no *exkl. MWST* line under either (Marcel).
	- ***Mehr über …*** is the *Detailbeschrieb*, the further images beside it
	  with their captions (the first is the hero's). Then *Weitere
	  Informationen*, *Kundenmeinungen*, and only then the recommendations,
	  which before stood above the copy: the mockup ends on what the Angebot
	  is, not on what else there is. The browse pair last.

	As on a course page, the phone's header row says the list's name.
--}}
<x-layout.site
	:title="$title"
	heading="Angebote"
	:description="$software->getTranslation('seo_description', $locale, false) ?: null"
	:keywords="$software->getTranslation('seo_tags', $locale, false) ?: null"
	:image="$og ? '/storage/uploads/'.$og->file : null"
>
	<article class="text-teal sm:flex sm:flex-col">
		<figure class="mb-24 sm:mb-32">
			@if ($visuals->isNotEmpty())
				<x-media.image
					:media="$visuals->first()"
					ratio="16/9"
					sizes="(min-width: 1132px) 1068px, 100vw"
					:max-width="1600"
					:alt="$title"
					loading="eager"
					class="block w-full"
				/>
			@else
				<div class="aspect-video w-full bg-gray-200"></div>
			@endif
		</figure>

		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="mb-12 sm:col-span-4">
				<h1 class="font-bold">{{ $title }}</h1>
				@if ($subtitle)
					<h2>{{ $subtitle }}</h2>
				@endif
			</div>

			<div class="sm:col-span-8">
				@if ($html = $software->getTranslation('short_description', $locale, false))
					<x-ui.rich-text :html="$html" hero />
				@endif
			</div>
		</div>
	</article>

	<div class="mt-48 lg:mt-64">
		@if ($courses->isNotEmpty())
			<x-ui.collapsible title="Kurse">
				@foreach ($courses as $course)
					@include('site.software._course', ['course' => $course])
				@endforeach

				<p class="mt-32 sm:mt-48 sm:text-lg lg:text-xl">
					{{ $title }} für dein ganzes Team?
					<a href="{{ \App\Support\SiteUrl::training() }}" class="text-teal hover:text-black">Firmenschulung anfragen</a>
				</p>
			</x-ui.collapsible>
		@endif

		{{-- None for an Angebot of courses only, Blender or After Effects. --}}
		@if ($products->isNotEmpty())
			<x-ui.collapsible title="Lizenzen">
				@foreach ($products as $product)
					@include('site.software._licence', ['product' => $product])
				@endforeach
			</x-ui.collapsible>
		@endif

		@if ($html = $software->getTranslation('full_description', $locale, false))
			<x-ui.collapsible :title="'Mehr über '.$title">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-ui.rich-text :html="$html" class="sm:col-span-8" />

					@if ($visuals->count() > 1)
						<div class="flex flex-col gap-24 max-sm:mt-32 sm:col-span-4">
							@foreach ($visuals->slice(1) as $visual)
								<figure>
									<x-media.image
										:media="$visual"
										ratio="16/9"
										sizes="(min-width: 1132px) 340px, (min-width: 700px) 33vw, 100vw"
										:max-width="1024"
										:alt="$visual->alt ?: $title"
										class="block w-full"
									/>
									@if ($visual->caption)
										<figcaption class="mt-8 text-gray-600">{{ $visual->caption }}</figcaption>
									@endif
								</figure>
							@endforeach
						</div>
					@endif
				</div>
			</x-ui.collapsible>
		@endif

		{{-- One column where a course has two, at *Detailbeschrieb*'s width. --}}
		@if ($information)
			<x-ui.collapsible title="Weitere Informationen">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-ui.rich-text :html="$information" class="sm:col-span-8" />
				</div>
			</x-ui.collapsible>
		@endif

		@if ($testimonials->isNotEmpty())
			<x-ui.collapsible title="Kundenmeinungen">
				<div class="grid grid-cols-12 gap-16 lg:gap-40">
					@foreach ($testimonials as $testimonial)
						<x-card.testimonial :testimonial="$testimonial" class="col-span-12 sm:col-span-6 lg:col-span-4" />
					@endforeach
				</div>
			</x-ui.collapsible>
		@endif

		{{-- Picked in the software's form ([[Recommendations]], client 2026-10-20):
		     plugins for it, courses, whatever makes the customer's bundle. --}}
		@if ($recommended['courses']->isNotEmpty() || $recommended['software']->isNotEmpty())
			<x-ui.collapsible title="Das könnte dich auch interessieren">
				<x-recommendations :courses="$recommended['courses']" :software="$recommended['software']" class="mt-16 sm:mt-32" />
			</x-ui.collapsible>
		@endif

		@if ($browse)
			<div class="mb-64 border-t border-gray-400 pt-8 sm:border-t-2 sm:pt-16 sm:text-lg lg:text-xl">
				<h2 class="mb-4 text-md leading-none font-bold text-gray-400 sm:text-lg lg:text-xl">Weitere Angebote</h2>

				<nav class="mt-32 flex justify-between" aria-label="Weitere Angebote">
					<a href="{{ \App\Support\SiteUrl::software($browse['prev']->getTranslation('slug', $locale)) }}"
						title="{{ $browse['prev']->getTranslation('title', $locale) }}"
						class="flex flex-col items-start hover:text-teal">
						<span>{{ $browse['prev']->getTranslation('title', $locale) }}</span>
						<x-icon.arrow-left class="mt-8" />
					</a>

					<a href="{{ \App\Support\SiteUrl::software($browse['next']->getTranslation('slug', $locale)) }}"
						title="{{ $browse['next']->getTranslation('title', $locale) }}"
						class="flex flex-col items-end hover:text-teal">
						<span>{{ $browse['next']->getTranslation('title', $locale) }}</span>
						<x-icon.arrow-right class="mt-8" />
					</a>
				</nav>
			</div>
		@endif
	</div>
</x-layout.site>
