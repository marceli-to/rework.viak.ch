@php
	$locale = app()->getLocale();
	$title = $course->getTranslation('title', $locale);
	$subtitle = $course->getTranslation('subtitle', $locale, false);
	$visuals = $course->visuals();
	$facts = collect($course->facts ?? [])
		->map(fn ($fact) => is_array($fact) ? ($fact[$locale] ?? reset($fact)) : $fact)
		->filter(fn ($text) => filled($text))
		->values();
	$booking = $course->getTranslation('information_booking', $locale, false);
	$content = $course->getTranslation('information_content', $locale, false);

	/*
	 * The per-course Open Graph crop, which the media port was flattening away
	 * until 2026-09-21 ([[09-public-site]]). The raw file rather than a Glide
	 * URL: the originals are already normalised on the way in, and a scraper
	 * fetching one is not a page-weight problem.
	 */
	$og = $course->openGraph();
@endphp

{{--
	`web/pages/courses/show.blade.php`, rebuilt 1:1 ([[09-public-site]]).

	Two parts. A **`content-text-media` hero** — the visual, then a `span-4`
	aside carrying the title and the expert line against a `span-8` column of
	short description, all of it teal — and then a stack of **collapsibles**:
	Aktuelle Kurse, Videos, Facts, Detailbeschrieb, Weitere Informationen, and
	the prev/next pair at the foot.

	Everything was measured against production on 2026-09-21 rather than read
	out of the SCSS; the numbers are in `09-public-site.md`.

	**The tab title and the page heading are different strings here.** Legacy
	sets `seo_title` to the course and `page_title` to *Kurse*, so the phone's
	header row says Kurse on every course page. It is the only page where the
	two differ.
--}}
<x-layout.site
	:title="$title"
	heading="Kurse"
	:description="$course->getTranslation('seo_description', $locale, false) ?: null"
	:keywords="$course->getTranslation('seo_tags', $locale, false) ?: null"
	:image="$og ? '/storage/uploads/'.$og->file : null"
>
	{{-- `layout/_article.scss:20` — `article.content-text-media`. Everything in
	     it is teal, headings and body copy alike, and `is-course` is the one
	     variant whose column is regular weight rather than bold. --}}
	<article class="text-teal sm:flex sm:flex-col">
		<figure class="mb-24 sm:mb-32">
			@if ($visuals->isNotEmpty())
				{{-- Legacy puts a Swiper here when a course has more than one
				     visual. **No course has**: 181 visuals across 32 courses and
				     not one with a second, checked 2026-09-21. A carousel for a
				     case that does not occur is a library and a set of controls
				     to maintain for nothing, so this renders the first and the
				     note stands in for it. --}}
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
				<h1 class="leading-[1.3] font-bold">{{ $title }}</h1>
				@if ($subtitle)
					<h2 class="leading-[1.3]">{{ $subtitle }}</h2>
				@endif
			</div>

			@if ($html = $course->getTranslation('short_description', $locale, false))
				<x-site.rich-text :html="$html" class="sm:col-span-8" />
			@else
				<div class="sm:col-span-8"></div>
			@endif
		</div>
	</article>

	{{-- `section.container-course`. The hero's body has a 40px row gap under it
	     already, so the stack starts straight in. --}}
	<div class="mt-48 lg:mt-64">
		<x-site.collapsible title="Aktuelle Kurse">
			@forelse ($course->events as $event)
				<x-site.event-card
					:event="$event"
					:bookmarked="in_array($event->id, $bookmarked, true)"
					:booked="in_array($event->id, $booked, true)"
				/>
			@empty
				<p class="mt-16 sm:mt-32">Neues Kursdatum folgt in Kürze</p>
			@endforelse
		</x-site.collapsible>

		@if ($course->videos->isNotEmpty())
			{{-- `course_videos`, which chunk 01 never ported — see
			     `09-public-site.md`. `code` is an `<iframe>` an editor pasted,
			     so it prints unescaped; the ratio box is legacy's
			     `.ratio-container`, which is what stops the embed from
			     collapsing to nothing. --}}
			<x-site.collapsible title="Videos">
				@foreach ($course->videos as $video)
					<div class="pt-16 sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-4">{{ $video->getTranslation('title', $locale, false) }}</div>
						<div class="mt-8 aspect-video sm:col-span-8 sm:mt-0 [&>iframe]:h-full [&>iframe]:w-full">
							{!! $video->code !!}
						</div>
					</div>
				@endforeach
			</x-site.collapsible>
		@endif

		@if ($facts->isNotEmpty())
			<x-site.collapsible title="Facts">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					@foreach ($facts as $fact)
						<x-site.rich-text :html="$fact" class="mb-16 sm:col-span-4 sm:mb-0" />
					@endforeach
				</div>
			</x-site.collapsible>
		@endif

		@if ($html = $course->getTranslation('full_description', $locale, false))
			<x-site.collapsible title="Detailbeschrieb">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-site.rich-text :html="$html" class="sm:col-span-8" />
				</div>
			</x-site.collapsible>
		@endif

		@if ($booking || $content || $course->reviews)
			<x-site.collapsible title="Weitere Informationen">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					@if ($booking)
						<x-site.rich-text :html="$booking" class="mb-16 sm:col-span-4 sm:mb-0" />
					@endif

					@if ($content)
						<x-site.rich-text :html="$content" class="mb-16 sm:col-span-4 sm:mb-0" />
					@endif

					{{-- **Empty on every course today, deliberately.** Legacy's
					     `courses.reviews` is not testimonials: all 32 non-empty
					     rows are an Elfsight widget embed, so the Kundenmeinungen
					     cards on the live page are Google reviews drawn by a
					     third party at run time. `PortCourses` reports the
					     column rather than carrying a third-party script over —
					     see `Open-Questions.md` #12. The column is here so that
					     whatever is decided has somewhere to land. --}}
					@if ($course->reviews)
						<div class="mb-16 sm:col-span-4 sm:mb-0">
							<header><h2 class="font-bold">Kundenmeinungen</h2></header>
						</div>
					@endif
				</div>
			</x-site.collapsible>
		@endif

		{{-- `.content-list-item` + `nav.browse`: a `#969696` rule, a grey label
		     and the neighbouring courses pushed to the two edges. Legacy wraps
		     at the ends of the catalogue rather than hiding an arrow. --}}
		@if ($browse)
			<div class="mb-64 border-t border-gray-400 pt-8 leading-[1.3] sm:border-t-2 sm:pt-16 sm:text-lg lg:text-xl">
				<h2 class="mb-4 text-md leading-none font-bold text-gray-400 sm:text-lg lg:text-xl">Weitere Kurse</h2>

				<nav class="mt-32 flex justify-between" aria-label="Weitere Kurse">
					<a href="{{ \App\Support\SiteUrl::course($browse['prev']->getTranslation('slug', $locale)) }}"
						title="{{ $browse['prev']->getTranslation('title', $locale) }}"
						class="flex flex-col items-start hover:text-teal">
						<span>{{ $browse['prev']->getTranslation('title', $locale) }}</span>
						<x-icon.arrow-left class="mt-8" />
					</a>

					<a href="{{ \App\Support\SiteUrl::course($browse['next']->getTranslation('slug', $locale)) }}"
						title="{{ $browse['next']->getTranslation('title', $locale) }}"
						class="flex flex-col items-end hover:text-teal">
						<span>{{ $browse['next']->getTranslation('title', $locale) }}</span>
						<x-icon.arrow-right class="mt-8" />
					</a>
				</nav>
			</div>
		@endif
	</div>

	{{-- The rental question and the confirmation that follows an add. One pair
	     for the whole page, however many events it lists. --}}
	<x-site.basket-dialogs />
</x-layout.site>
