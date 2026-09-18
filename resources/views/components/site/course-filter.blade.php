@props(['software', 'active' => null])

@php($locale = app()->getLocale())

{{--
	`components/_filter.scss`. Legacy offers categories as links and then Ort,
	Software, Level, Sprache, Experte and Tags as selects. Only Software is
	wired in the rework so far — the other taxonomies exist but nothing filters
	on them yet — so the rest are not drawn rather than drawn dead.
--}}
<div class="mt-32 sm:mt-0">
	<h2 class="mb-16 text-lg sm:text-xl lg:text-3xl">Filter</h2>

	<ul>
		@foreach ($software as $item)
			<li class="border-b border-gray-400">
				<a href="{{ request()->fullUrlWithQuery(['software' => $item->uuid === $active ? null : $item->uuid]) }}"
					@class([
						'block py-8 text-md hover:text-teal lg:text-lg',
						'text-teal' => $item->uuid === $active,
					])>
					{{ $item->getTranslation('title', $locale) }}
				</a>
			</li>
		@endforeach
	</ul>

	@if ($active)
		<a href="{{ request()->fullUrlWithQuery(['software' => null]) }}"
			class="mt-24 block border border-teal py-8 text-center text-md text-teal hover:bg-teal hover:text-white lg:text-lg">
			Zurücksetzen
		</a>
	@endif

	{{-- `.card-teaser-training` — the teal promo box under the filter. --}}
	<div class="mt-32 block bg-teal p-12 text-white">
		<p class="text-lg leading-[1.4] font-bold break-words text-white lg:text-xl">
			Wünschen Sie eine massgeschneiderte Individualschulung für Einzelpersonen oder Ihre Firma?
		</p>
		<x-icon.arrow-right class="mt-16 size-24" />
	</div>
</div>
