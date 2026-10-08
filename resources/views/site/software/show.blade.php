@php
	$locale = app()->getLocale();
	$title = $software->getTranslation('title', $locale);
	$subtitle = $software->getTranslation('subtitle', $locale, false);
	$visuals = $software->visuals();
	$information = $software->getTranslation('information', $locale, false);
	$more = $software->getTranslation('information_more', $locale, false);
	$og = $software->openGraph();
@endphp

{{--
	One software ([[05-licences]]), drawn as a course page is
	(`site/courses/show.blade.php`, Marcel 2026-10-08): the teal hero, then the
	collapsibles. *Aktuelle Kurse* becomes *Lizenzen*, every product with its
	licences (`card/product.blade.php`); *Kurse* lists the courses that teach
	it as the course list's cards; *Detailbeschrieb*, *Weitere Informationen*,
	*Kundenmeinungen* and the browse pair are the course page's.

	As on a course page, the phone's header row says the list's name.
--}}
<x-layout.site
	:title="$title"
	heading="Software"
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

			@if ($html = $software->getTranslation('short_description', $locale, false))
				<x-ui.rich-text :html="$html" hero class="sm:col-span-8" />
			@else
				<div class="sm:col-span-8"></div>
			@endif
		</div>
	</article>

	<div class="mt-48 lg:mt-64">
		<x-ui.collapsible title="Lizenzen">
			@foreach ($products as $product)
				<x-card.product :product="$product" />
			@endforeach

			{{-- The shop's prices are net, VAT goes on the invoice ([[05-licences]]). --}}
			<p class="mt-16 sm:mt-32 sm:text-lg lg:text-xl">Preise exkl. MWST.</p>
		</x-ui.collapsible>

		@if ($courses->isNotEmpty())
			<x-ui.collapsible title="Kurse">
				<div class="mt-16 grid grid-cols-12 gap-16 sm:mt-32 lg:gap-40">
					@foreach ($courses as $course)
						<x-card.course :course="$course" class="col-span-6 lg:col-span-4" />
					@endforeach
				</div>
			</x-ui.collapsible>
		@endif

		@if ($html = $software->getTranslation('full_description', $locale, false))
			<x-ui.collapsible title="Detailbeschrieb">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-ui.rich-text :html="$html" class="sm:col-span-8" />
				</div>
			</x-ui.collapsible>
		@endif

		@if ($information || $more)
			<x-ui.collapsible title="Weitere Informationen">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					@if ($information)
						<x-ui.rich-text :html="$information" class="mb-16 sm:col-span-4 sm:mb-0" />
					@endif

					@if ($more)
						<x-ui.rich-text :html="$more" class="mb-16 sm:col-span-4 sm:mb-0" />
					@endif
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

		@if ($browse)
			<div class="mb-64 border-t border-gray-400 pt-8 sm:border-t-2 sm:pt-16 sm:text-lg lg:text-xl">
				<h2 class="mb-4 text-md leading-none font-bold text-gray-400 sm:text-lg lg:text-xl">Weitere Software</h2>

				<nav class="mt-32 flex justify-between" aria-label="Weitere Software">
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
