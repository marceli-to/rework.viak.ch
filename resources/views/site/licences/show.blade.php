@php
	$locale = app()->getLocale();
	$angebot = $software->getTranslation('title', $locale);
	$title = $angebot.' Lizenzen';
	$visuals = $software->visuals();
	$og = $software->openGraph();
	$text = fn (string $field) => trim((string) $software->getTranslation($field, $locale, false));
	$lead = $text('licences_lead');
	$quote = $text('licences_quote');
	$requirements = $software->getTranslation('requirements', $locale, false);
@endphp

{{--
	**An Angebot's licence page** (#71, the client 2026-11-12, [[14-angebote]]
	*As built: step 5*), its order the client's docs' (`../Software/Corona`):
	*{Angebot} Lizenzen* and the *Sublead*, every licence as a block of its
	own, *Systemvoraussetzungen* once for all, and the page's own Zitatbox.
	Drawn as the Angebot page is (`site/software/show.blade.php`), a course
	page's teal hero and collapsibles.

	**Product by product** (`card/product.blade.php`, the card staging had per
	product): its name and maker as a heading, its description, then one row
	per licence with its *Hinweis*, price, *Anzahl* and button. Each product
	is anchored by its slug, which is where a product's old URL and every row
	and tile that names it lead ([[SiteUrl::licence]]).

	No breadcrumb (the site has none): the way back to the Angebot is the
	link under the title. The hero takes the Angebot's picture.
--}}
<x-layout.site
	:title="$title"
	heading="Software"
	:description="$lead ?: null"
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
					:alt="$angebot"
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
				<x-ui.back-link :href="\App\Support\SiteUrl::software($software->getTranslation('slug', $locale))" :label="'Alles zu '.$angebot" direction="right" class="mt-16 text-black" />
			</div>

			<div class="sm:col-span-8">
				@if ($lead)
					<x-ui.rich-text :html="'<p>'.e($lead).'</p>'" hero />
				@endif
			</div>
		</div>
	</article>

	<div class="mt-48 lg:mt-64">
		<x-ui.collapsible title="Lizenzen">
			@foreach ($software->products as $product)
				<x-card.product :product="$product" />
			@endforeach

			{{-- The shop's prices are net, VAT goes on the invoice ([[05-licences]]). --}}
			<p class="mt-32 sm:mt-48 sm:text-lg lg:text-xl">Alle Preise exkl. {{ config('invoice.vat_label') }}</p>
		</x-ui.collapsible>

		{{-- Once per Angebot, not per licence (the client, 2026-11-12). --}}
		@if (filled(strip_tags((string) $requirements)))
			<x-ui.collapsible title="Systemvoraussetzungen">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-ui.rich-text :html="$requirements" class="sm:col-span-8" />
				</div>
			</x-ui.collapsible>
		@endif

		{{-- The page's own Zitatbox, drawn as the Angebot page's pull quote. --}}
		@if ($quote)
			<div class="mb-64 sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<blockquote class="text-2xl leading-[1.3] text-teal text-center sm:col-span-8 sm:col-start-3 sm:text-3xl lg:text-4xl">«{{ $quote }}»</blockquote>
			</div>
		@endif
	</div>

	{{-- *Die Lizenz wurde im Warenkorb abgelegt.*, once per page. --}}
	<x-dialog.basket />
</x-layout.site>
