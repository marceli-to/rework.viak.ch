@props(['software', 'active' => null, 'matching' => 0])

@php
	$locale = app()->getLocale();

	/*
	 * The no-JavaScript href for a given selection. `fullUrlWithQuery()` leaves a
	 * bare `?` behind when the last filter is cleared; this drops it, and keeps
	 * any other parameter, so the six attributes still to be wired combine
	 * without each one clobbering the rest.
	 */
	$urlFor = function (array $changes) {
		$query = array_filter(
			array_merge(request()->query(), $changes),
			fn ($value) => $value !== null && $value !== '',
		);

		return request()->url().($query ? '?'.http_build_query($query) : '');
	};
@endphp

{{--
	`components/_filter.scss`.

	On a phone this is a **full-screen white panel**, opened from the icon beside
	the page title and closed from the cross at its top right. `padding-top: 88px`
	is legacy's own arithmetic — 48px of header, 24px of header margin and 16px of
	body padding — so the panel's first line lands where the page's would.

	From 700px it is simply the right-hand column and always visible.

	**Every control here works twice.** The `href` is the no-JavaScript path and
	the one a crawler follows; the `@click.prevent` beside it is what actually
	runs in a browser, because on a phone following the link would reload the
	page and close the panel mid-use ([[09-public-site]]). The two produce the
	same view.

	Only Software is wired so far. Legacy also filters by category and by Ort,
	Level, Sprache, Experte and Tags; those taxonomies exist but nothing filters
	on them yet, so they are left out rather than drawn dead.
--}}
{{-- The breakpoint is CSS, not JavaScript: `max-sm:hidden` when closed, nothing
     when open, and from `sm` the panel is a static column regardless. Binding
     `x-show` to a media query instead would not survive a resize, and would hide
     the desktop column until Alpine had started. --}}
<div
	:class="open ? '' : 'max-sm:hidden'"
	class="fixed inset-0 z-[100] h-full w-full overflow-y-auto bg-white px-16 pt-88 pb-48 sm:static sm:z-auto sm:block sm:h-auto sm:overflow-visible sm:p-0"
>
	<div class="flex justify-end sm:hidden">
		<button type="button" @click="close()" aria-label="Filter schliessen">
			<x-icon.cross size="large" />
		</button>
	</div>

	<h2 class="mb-32 text-lg font-bold">Filter</h2>

	<ul class="border-t border-gray-400">
		@foreach ($software as $item)
			<li class="flex min-h-40 items-center border-b border-gray-400">
				<a href="{{ $urlFor(['software' => $item->uuid === $active ? null : $item->uuid]) }}"
					@click.prevent="toggle('software', @js($item->uuid))"
					:class="{ 'font-bold text-gray-400': selected.software === @js($item->uuid) }"
					@class([
						'block w-full text-lg hover:text-teal',
						'font-bold text-gray-400' => $item->uuid === $active,
					])>
					{{ $item->getTranslation('title', $locale) }}
				</a>
			</li>
		@endforeach
	</ul>

	<div class="mt-32 flex flex-col items-center gap-16 sm:items-start">
		{{-- Legacy's own label: `Anzeigen (12)`, with the count dropped when it
		     is zero. There is nothing to apply — the list behind the panel is
		     already filtered — so this only closes the panel. --}}
		<x-site.button class="w-full sm:hidden" @click="apply()">
			Anzeigen <span x-text="count ? `(${count})` : ''">{{ $matching ? '('.$matching.')' : '' }}</span>
		</x-site.button>

		{{-- Rendered even with nothing selected, so Alpine has something to
		     reveal; without JavaScript it is simply never shown. --}}
		<x-site.button
			variant="outline"
			:href="$urlFor(['software' => null])"
			@click.prevent="reset()"
			::class="{ hidden: !active }"
			@class(['w-full', 'hidden' => ! $active])
		>
			Zurücksetzen
		</x-site.button>
	</div>

	{{-- `.card-teaser-training` — the teal promo box under the filter. --}}
	<div class="mt-32 block bg-teal p-12 text-white">
		<p class="text-lg leading-[1.4] font-bold break-words hyphens-auto text-white lg:text-xl">
			Wünschen Sie eine massgeschneiderte Individualschulung für Einzelpersonen oder Ihre Firma?
		</p>
		<x-icon.arrow-right class="mt-16" />
	</div>
</div>
