@php
	$locale = app()->getLocale();
	$title = $software->getTranslation('title', $locale);
	$subtitle = $software->getTranslation('subtitle', $locale, false);
	$visuals = $software->visuals();
	$information = $software->getTranslation('information', $locale, false);
	$og = $software->openGraph();

	// The client's website texts (#70), plain text, each optional.
	$text = fn (string $field) => trim((string) $software->getTranslation($field, $locale, false));
	$audiences = $text('audiences');
	$intro = $text('intro');
	$coursesIntro = $text('courses_intro');
	$licencesIntro = $text('licences_intro');
	$quote = $text('quote');
	$about = filled($software->getTranslation('full_description', $locale, false));
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
	- **Kurse, then Lizenzen, then Plugins** (the client, 2026-11-03, #67:
	  the products marked *Plugin*), each a full-width collapsible (Marcel,
	  2026-10-29: the two columns side by side of the first version were too
	  cramped). **Under Kurse every date is the course page's own row**
	  (`card/event.blade.php`) with the course's name over it, teal and
	  linked, no heart, and *Details* to the course page for *Buchen*
	  (`titled`): nothing is booked here; a course without a date says
	  *Neues Kursdatum folgt in Kürze*. A licence row (`_licence.blade.php`)
	  is drawn in the same columns and links to the licence's page. Under the courses the way to a
	  Firmenschulung; no *exkl. MWST* line under either (Marcel).
	- **The client's website texts** (#70, 2026-11-06, [[14-angebote]] *The
	  website texts*), each only when filled: *Zielgruppen* and the intro
	  sentence under the hero's text, a paragraph at the head of *Kurse* and
	  of *Lizenzen*, and the **quote** after the licences, a pull quote
	  in «».
	- ***Was ist …?*** (the *Detailbeschrieb*) is **a page of its own** since
	  #70, for Google ([[SoftwareController::about]]); the hero links to it.
	  Until then it was a collapsible here, *Mehr über …*. Then *Weitere
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

				@if ($audiences || $intro || $about)
					<div @class(['[&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0', 'mt-12 lg:mt-16' => filled($software->getTranslation('short_description', $locale, false))])>
						@if ($audiences)
							<p><span class="font-bold">Zielgruppen:</span> {{ $audiences }}</p>
						@endif
						@if ($intro)
							<p>{{ $intro }}</p>
						@endif
						@if ($about)
							<p>
								<a href="{{ \App\Support\SiteUrl::softwareAbout($software->getTranslation('slug', $locale)) }}"
									class="underline decoration-1 underline-offset-[3px] hover:no-underline sm:decoration-2 sm:underline-offset-[5px]">Was ist {{ $title }}?</a>
							</p>
						@endif
					</div>
				@endif
			</div>
		</div>
	</article>

	<div class="mt-48 lg:mt-64">
		@if ($courses->isNotEmpty())
			<x-ui.collapsible title="Kurse">
				@if ($coursesIntro)
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40"><p class="sm:col-span-8">{{ $coursesIntro }}</p></div>
				@endif

				{{-- Every date as the course page draws it (`card/event.blade.php`),
				     the course's name over it (Marcel, 2026-10-29); a course with
				     none says what its own page says. --}}
				@foreach ($courses as $course)
					@forelse ($course->events as $event)
						<x-card.event :event="$event" titled />
					@empty
						<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
							<a href="{{ \App\Support\SiteUrl::course($course->getTranslation('slug', $locale)) }}"
								class="mb-8 block font-bold text-teal hover:text-black sm:mb-16">{{ $course->getTranslation('title', $locale) }}</a>
							<p>Neues Kursdatum folgt in Kürze</p>
						</article>
					@endforelse
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
				@if ($licencesIntro)
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40"><p class="sm:col-span-8">{{ $licencesIntro }}</p></div>
				@endif

				@foreach ($products as $product)
					@include('site.software._licence', ['product' => $product])
				@endforeach
			</x-ui.collapsible>
		@endif

		{{-- The plugins sold under it (Bongo under Rhinoceros, #67), the same rows. --}}
		@if ($plugins->isNotEmpty())
			<x-ui.collapsible title="Plugins">
				@foreach ($plugins as $product)
					@include('site.software._licence', ['product' => $product])
				@endforeach
			</x-ui.collapsible>
		@endif

		{{-- The client's *Zitatbox* (#70) as a pull quote (Marcel, 2026-11-10):
		     no frame, the scale's largest sizes, in «», centred on the page in
		     its middle eight columns. --}}
		@if ($quote)
			<div class="mb-64 sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<blockquote class="text-2xl leading-[1.3] text-teal text-center sm:col-span-8 sm:col-start-3 sm:text-3xl lg:text-4xl">«{{ $quote }}»</blockquote>
			</div>
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
