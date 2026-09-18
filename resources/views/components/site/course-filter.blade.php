@props(['software', 'active' => null])

@php($locale = app()->getLocale())

{{--
	`components/_filter.scss`. Legacy offers categories as links and then Ort,
	Software, Level, Sprache, Experte and Tags as selects. Only Software is
	wired in the rework so far — the other taxonomies exist but nothing filters
	on them yet — so the rest are not drawn rather than drawn dead.
--}}
<div class="mt-8 sm:mt-0">
	<h2 class="mb-4 text-base sm:text-lg md:text-2xl">Filter</h2>

	<ul>
		@foreach ($software as $item)
			<li class="border-b border-line">
				<a href="{{ request()->fullUrlWithQuery(['software' => $item->uuid === $active ? null : $item->uuid]) }}"
					@class([
						'block py-2 text-sm hover:text-teal md:text-base',
						'text-teal' => $item->uuid === $active,
					])>
					{{ $item->getTranslation('title', $locale) }}
				</a>
			</li>
		@endforeach
	</ul>

	@if ($active)
		<a href="{{ request()->fullUrlWithQuery(['software' => null]) }}"
			class="mt-6 block border border-teal py-2 text-center text-sm text-teal hover:bg-teal hover:text-white md:text-base">
			Zurücksetzen
		</a>
	@endif

	{{-- `.card-teaser-training` — the teal promo box under the filter. --}}
	<div class="mt-8 block bg-teal p-3 text-white">
		<p class="text-[16px] leading-[1.4] font-bold break-words text-white md:text-[18px]">
			Wünschen Sie eine massgeschneiderte Individualschulung für Einzelpersonen oder Ihre Firma?
		</p>
		<div class="mt-4 text-2xl leading-none">→</div>
	</div>
</div>
