@php
	$locale = app()->getLocale();
	$title = $software->getTranslation('title', $locale);
	$heading = "Was ist {$title}?";
	$html = $software->getTranslation('full_description', $locale);
	$visuals = $software->visuals();
	$og = $software->openGraph();
@endphp

{{--
	An Angebot's *Was ist …?* page (#70, the client 2026-11-06, [[14-angebote]]
	*The website texts*): its *Detailbeschrieb* as a page of its own, meant as
	a way in from Google (`Alle Angebote/Rhino/Was-ist-Rhino` in the client's
	texts). Until then the text was the Angebot page's *Mehr über …*.

	The Angebot's image on top, as on its page, then the site's text page
	(`layout/article`): the heading and the way back to the Angebot in the
	aside, the text in the column, the further images under it with their
	captions (*Mehr über …* had them beside the text).

	The description for search engines is the text's opening, cut: the
	Angebot's own SEO description is about the Angebot, not this page.
--}}
<x-layout.site
	:title="$heading"
	heading="Angebote"
	:description="\Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('</p>', '</p> ', $html)))), 155)"
	:image="$og ? '/storage/uploads/'.$og->file : null"
>
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

	<x-layout.article class="mb-64">
		<x-slot:aside>
			<h1 class="font-bold text-teal">{{ $heading }}</h1>
			<x-ui.back-link :href="\App\Support\SiteUrl::software($software->getTranslation('slug', $locale))" :label="$title" />
		</x-slot:aside>

		<x-ui.rich-text :html="$html" />

		@foreach ($visuals->slice(1) as $visual)
			<figure class="mt-32 sm:mt-48">
				<x-media.image
					:media="$visual"
					ratio="16/9"
					sizes="(min-width: 1132px) 700px, (min-width: 700px) 66vw, 100vw"
					:max-width="1600"
					:alt="$visual->alt ?: $title"
					class="block w-full"
				/>
				@if ($visual->caption)
					<figcaption class="mt-8 text-gray-600">{{ $visual->caption }}</figcaption>
				@endif
			</figure>
		@endforeach
	</x-layout.article>
</x-layout.site>
