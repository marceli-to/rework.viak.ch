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

	**One block per licence** (`card/licence-block.blade.php`, Marcel
	2026-11-17, as the docs have it): *Corona Solo*, then its kind, text,
	price and button. Until then product by product, a heading over its
	licences' rows. A product's first block is anchored by its slug, which is
	where its old URL and every row and tile that names it lead
	([[SiteUrl::licence]]).

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
				{{-- The logo beside the title (Oliver, 2026-11-17; Marcel: side by side, not
				     over it); none where it has none, and the title alone. --}}
				<div class="flex items-center gap-12 lg:gap-16">
					<x-ui.logo :src="$software->logoUrl()" class="size-40 lg:size-48" />
					<h1 class="min-w-0 font-bold">{{ $title }}</h1>
				</div>
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
				@foreach ($product->variants->filter(fn ($variant) => $variant->listed)->values() as $variant)
					<x-card.licence-block :product="$product" :variant="$variant" :anchor="$loop->first" />
				@endforeach
			@endforeach

			{{-- The shop's prices are net, VAT goes on the invoice ([[05-licences]]). --}}
			<p class="mt-32 sm:mt-48 sm:text-lg lg:text-xl">Alle Preise exkl. {{ config('invoice.vat_label') }}</p>
		</x-ui.collapsible>

		{{-- Once per Angebot, not per licence (the client, 2026-11-12).

		     Oliver's blocks are a bold label in a paragraph of its own
		     (*Betriebssystem (mindestens):*) and a list or a sentence under it
		     ([[ImportRequirements]]). Rich text's spacing alone glued each list
		     to the next label and left the label floating above its own list
		     (Marcel, 2026-11-26: "unfinished"). So here, and only here: a label
		     sits tight on what it heads (4/8px), a group starts 24/32px below
		     the last, and a sentence that introduces a list (*Zusätzlich:*)
		     sits tight on it too. A product's heading (V-Ray, Lumion, RealFlow
		     hold several) is teal over a rule, as the licence blocks' titles. --}}
		@if (filled(strip_tags((string) $requirements)))
			<x-ui.collapsible title="Systemvoraussetzungen">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<x-ui.rich-text :html="$requirements" @class([
						'sm:col-span-8',
						'[&_ul]:mb-12 lg:[&_ul]:mb-16 [&_ul:last-child]:mb-0',
						'[&_p:has(>strong:only-child)]:mb-4 lg:[&_p:has(>strong:only-child)]:mb-8',
						'[&_p:has(>strong:only-child):not(:first-child)]:mt-24 lg:[&_p:has(>strong:only-child):not(:first-child)]:mt-32',
						'[&_p:has(+ul)]:mb-4 lg:[&_p:has(+ul)]:mb-8',
						'[&_h3]:mb-16 [&_h3]:text-teal lg:[&_h3]:mb-24',
						'[&_h3:not(:first-child)]:mt-40 [&_h3:not(:first-child)]:border-t [&_h3:not(:first-child)]:border-black [&_h3:not(:first-child)]:pt-16 lg:[&_h3:not(:first-child)]:mt-56 lg:[&_h3:not(:first-child)]:pt-24',
						'[&_h3+p]:mt-0!',
					]) />
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
