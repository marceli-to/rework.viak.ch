{{--
	One Vorhaben ([[04-content]]), at `/de/vorhaben/{slug}`. The 2026-09-23
	review cut the Räume mockup to **a title, a text and the offer list**
	(markers 1–3): the tools box beside the headline, the phone box and
	*Andere Vorhaben* at the foot are not built, and nor is the breadcrumb,
	which the site has nowhere.

	**The mockup's content in the site's look** (the rule for every new page,
	Marcel, 2026-10-06): the top is drawn as Firmenschulung's
	([[training._intro]]), the title teal in the aside, the lead the column's
	bold `h2`, the text below it. The offer list is the Kurse page's own cards,
	`x-card.course`: `span-6`, `span-4` from sm, which is the width they have
	in the Kurse grid, so they draw the same. **Then the software**
	(`x-card.software`), picked in the same form (Marcel, 2026-10-09), each
	card tagged *Kurs* or *Software* as *Beliebte Angebote* does. The mockup's
	*Alle / Kurse / Software* chips are not built.
--}}
@php
	$locale = app()->getLocale();
	$title = $project->getTranslation('title', $locale);
	$lead = $project->getTranslation('lead', $locale, false);
	$text = $project->getTranslation('text', $locale, false);
	// *Metatags + SEO* from the form; without a description, the lead stands in.
	$description = $project->getTranslation('seo_description', $locale, false) ?: $lead;
	$keywords = $project->getTranslation('seo_tags', $locale, false);
@endphp

<x-layout.site :title="$title" :heading="$title" :description="$description ?: null" :keywords="$keywords ?: null">
	<article class="mb-48 sm:grid sm:grid-cols-12 sm:gap-16 lg:mb-64 lg:gap-40">
		{{-- `xs:hide`, as on Kontakt: on a phone the header row already says it. --}}
		<aside class="text-teal max-sm:hidden sm:col-span-4">
			<h1 class="font-bold">{{ $title }}</h1>
		</aside>

		<div class="sm:col-span-8">
			@if ($lead)
				<h2 @class(['font-bold', 'mb-12 lg:mb-16' => filled($text)])>{{ $lead }}</h2>
			@endif

			@if (filled($text))
				<x-ui.rich-text :html="$text" />
			@endif
		</div>
	</article>

	@if ($courses->isNotEmpty() || $software->isNotEmpty())
		<div class="grid grid-cols-12 gap-16 lg:gap-40">
			@foreach ($courses as $course)
				<x-card.course :course="$course" kind="Kurs" :eager="$loop->index < 3" class="col-span-6 sm:col-span-4" />
			@endforeach

			@foreach ($software as $item)
				<x-card.software :software="$item" kind="Software" :eager="$courses->count() + $loop->index < 3" class="col-span-6 sm:col-span-4" />
			@endforeach
		</div>
	@endif
</x-layout.site>
