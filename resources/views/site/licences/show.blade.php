@php
	$locale = app()->getLocale();
	$title = $product->getTranslation('title', $locale);
	$angebot = $software->getTranslation('title', $locale);
	$visuals = $software->visuals();
	$features = $product->featureList($locale);
	$og = $software->openGraph();
@endphp

{{--
	One licence product, where it is bought ([[14-angebote]]): the product
	level under the Angebot (the client, 2026-10-29). Drawn as the software
	page is (`site/software/show.blade.php`), a course page's teal hero and
	collapsibles, so a product page looks like the course pages beside it.

	The product has no pictures of its own; the hero takes its software's.
	**No breadcrumb** (the site has none): the way back to the Angebot is the
	link under the title. The description and the features are the hero's
	right column, and *Lizenzen* the card without its head
	(`card/product.blade.php`, `bare`).
--}}
<x-layout.site
	:title="$title"
	heading="Software"
	:description="str($product->getTranslation('description', $locale, false) ?? '')->stripTags()->squish()->limit(160)->value() ?: null"
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
				@if ($product->manufacturer)
					<h2>von {{ $product->manufacturer->getTranslation('title', $locale) }}</h2>
				@endif
				<x-ui.back-link :href="\App\Support\SiteUrl::software($software->getTranslation('slug', $locale))" :label="'Alles zu '.$angebot" direction="right" class="mt-16 text-black" />
			</div>

			<div class="sm:col-span-8">
				@if ($html = $product->getTranslation('description', $locale, false))
					<x-ui.rich-text :html="$html" hero />
				@endif

				@if ($features !== [])
					<ul @class(['flex flex-col gap-8 text-black sm:text-lg lg:text-xl', 'mt-24' => filled($html)])>
						@foreach ($features as $feature)
							<li class="flex items-baseline gap-8">
								<x-icon.checkmark class="w-16 shrink-0 text-teal" />
								{{ $feature }}
							</li>
						@endforeach
					</ul>
				@endif
			</div>
		</div>
	</article>

	<div class="mt-48 mb-64 lg:mt-64">
		<x-ui.collapsible title="Lizenzen">
			<x-card.product :product="$product" bare />

			{{-- The shop's prices are net, VAT goes on the invoice ([[05-licences]]). --}}
			<p class="mt-32 sm:mt-48 sm:text-lg lg:text-xl">Alle Preise exkl. {{ config('invoice.vat_label') }}</p>
		</x-ui.collapsible>
	</div>

	{{-- *Die Lizenz wurde im Warenkorb abgelegt.*, once per page. --}}
	<x-dialog.basket />
</x-layout.site>
