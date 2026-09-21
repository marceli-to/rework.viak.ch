@props(['filter', 'matching' => 0])

@php
	$selected = $filter->selected();
	$options = $filter->options();

	/*
	 * The no-JavaScript href for a given change. Keeps every other attribute, so
	 * the seven combine rather than clobber each other, and drops the bare `?`
	 * that `fullUrlWithQuery()` leaves behind when the last one is cleared.
	 */
	$urlFor = function (array $changes) {
		$query = array_filter(
			array_merge(request()->query(), $changes),
			fn ($value) => $value !== null && $value !== '',
		);

		return request()->url().($query ? '?'.http_build_query($query) : '');
	};

	/*
	 * `%icon-chevron-down` — a CSS triangle, not artwork: 5px transparent sides
	 * under an 8px #505050 top, right-aligned and vertically centred, stepping
	 * to 6/9 at legacy's `bp-sm`.
	 */
	$chevron = "after:absolute after:top-1/2 after:right-0 after:h-0 after:w-0 after:-translate-y-1/2 after:border-x-[5px] after:border-t-[8px] after:border-x-transparent after:border-t-gray-600 after:content-[''] after:pointer-events-none sm:after:border-x-[6px] sm:after:border-t-[9px]";
@endphp

{{--
	`components/_filter.scss` and `frontend/filter/Index.vue`.

	On a phone this is a **full-screen white panel**, opened from the icon beside
	the page title and closed from the cross at its top right. `padding-top: 88px`
	is legacy's own arithmetic — 48px of header, 24px of header margin and 16px of
	body padding — so the panel's first line lands where the page's would.

	From 700px it is simply the right-hand column and always visible.

	**One list of nine items**, as legacy builds it: a single rule on top, a rule
	under each item, the three categories as links and the other six as selects,
	with 40px of air before *Ort* — the only thing separating the two halves.

	**Every control works twice.** The `href` is the no-JavaScript path and the
	one a crawler follows; the `@click.prevent` beside it is what actually runs
	in a browser, because on a phone following the link would reload the page and
	close the panel mid-use ([[09-public-site]]). A select cannot be a link, so
	the six of them submit the surrounding form instead — that is what the
	`sr-only` button is for, and with Alpine running nothing ever submits it.
--}}
{{--
	`.icon-filter` — **one control, not two.** Legacy renders a single fixed
	22×22 button that draws the funnel when the panel is closed and the cross
	when it is open (`shared/components/ui/icons/Filter.vue`, which swaps its
	path on an `active` prop). `position: fixed; top: 26px; right: 16px` over
	`z-index: 101` puts it above the panel and, at the top of the page, exactly
	beside the page title — which is why an earlier pass mistook it for part of
	the header and gave the panel a second, larger cross of its own. That one sat
	*in the flow*, so it also pushed `Filter` 30px below legacy's 88.
--}}
<button
	type="button"
	class="fixed top-26 right-16 z-[101] block h-22 w-22 sm:hidden"
	@click="open = ! open"
	:aria-expanded="open"
	aria-label="Filter"
>
	<span :class="{ hidden: open }"><x-icon.filter /></span>
	<span class="hidden" :class="{ hidden: ! open }"><x-icon.cross /></span>
</button>

{{-- The breakpoint is CSS, not JavaScript: `max-sm:hidden` when closed, nothing
     when open, and from `sm` the panel is a static column regardless. Binding
     `x-show` to a media query instead would not survive a resize, and would hide
     the desktop column until Alpine had started. --}}
<div
	:class="open ? '' : 'max-sm:hidden'"
	class="fixed inset-0 z-[100] h-full w-full overflow-y-auto bg-white px-16 pt-88 pb-48 sm:static sm:z-auto sm:block sm:h-auto sm:overflow-visible sm:p-0"
>
	{{-- `leading-[1.3]` is the body line-height legacy inherits here. Without
	     it Tailwind's own `text-lg` pairing (1.556) applies and every row below
	     sits 4px low — see `resources/css/README.md`. --}}
	<h2 class="mb-32 text-lg leading-[1.3] font-bold">Filter</h2>

	<form method="get" action="{{ request()->url() }}" @submit.prevent="apply()">
		{{-- Category is chosen by link rather than by control, so a submit without
		     JavaScript would otherwise drop it. --}}
		<input type="hidden" name="category" value="{{ $selected['category'] }}" :value="selected.category">

		<ul class="border-t border-gray-400">
			{{-- The categories, as links. --}}
			@foreach ($options['category'] as $uuid => $label)
				<li class="flex min-h-40 items-center border-b border-gray-400">
					{{-- **Bold and black**, not bold and grey. `.filter__item.is-active`
					     does say `color: $color-tertiary`, but it sets it on the
					     item and the `a` inside carries its own colour — so the
					     grey never lands anywhere and the live page shows black.
					     Reading the rule instead of the page is the trap this
					     chunk opens with ([[09-public-site]]). --}}
					<a href="{{ $urlFor(['category' => $uuid === $selected['category'] ? null : $uuid]) }}"
						@click.prevent="toggle('category', @js($uuid))"
						:class="{ 'font-bold': selected.category === @js($uuid) }"
						@class([
							'block w-full text-lg leading-[1.3] hover:text-teal',
							'font-bold' => $uuid === $selected['category'],
						])>
						{{ $label }}
					</a>
				</li>
			@endforeach

			{{-- The other six, as selects. `Ort` carries the 40px that separates
			     the halves; the rest follow it with none. --}}
			@foreach (['location' => 'Ort', 'software' => 'Software', 'level' => 'Level', 'language' => 'Sprache', 'expert' => 'Experte', 'tag' => 'Tags'] as $attribute => $placeholder)
				<li @class(['flex min-h-40 items-center border-b border-gray-400', 'mt-40' => $loop->first])>
					{{-- `min-height: inherit` on legacy's `.select-wrapper`: it takes
					     the item's 40px, so a select row is 40 **plus** its rule
					     while a category row is 40 including it. That 1px per row
					     is legacy's, and it is what the live page measures. --}}
					<div class="relative flex min-h-40 w-full items-center py-8 {{ $chevron }}">
						{{-- A chosen value goes bold, the same way an active category
						     does — `.filter__item.is-active select` — and stays black,
						     because the select carries its own colour. `outline-hidden`
						     is legacy's `outline: none !important` on every form
						     control; it keeps the outline in forced-colors mode, which
						     is the one place it still has a job. --}}
						<select
							name="{{ $attribute }}"
							aria-label="{{ $placeholder }}"
							@class([
								'block w-full cursor-pointer appearance-none bg-transparent pr-16 text-lg leading-[1.3] text-black outline-hidden',
								'font-bold' => $selected[$attribute] !== null,
							])
							:class="{ 'font-bold': selected.{{ $attribute }} }"
							x-model="selected.{{ $attribute }}"
							@change="sync()"
						>
							<option value="">{{ $placeholder }}</option>
							@foreach ($options[$attribute] as $value => $label)
								<option value="{{ $value }}" @selected($value === $selected[$attribute])>{{ $label }}</option>
							@endforeach
						</select>
					</div>
				</li>
			@endforeach
		</ul>

		{{-- What makes the six selects work with JavaScript off. Never seen and
		     never needed otherwise — legacy has no equivalent because legacy's
		     filter does not work without JavaScript at all. --}}
		<button type="submit" class="sr-only">Filter anwenden</button>

		<div class="mt-40 flex flex-col items-center gap-16 sm:mt-16 sm:items-start">
			{{-- Legacy's own label: `Anzeigen (12)`, with the count dropped when
			     it is zero. There is nothing to apply — the list behind the panel
			     is already filtered — so with Alpine this only closes the panel. --}}
			{{-- Label and count are **one** text node. The button is a flex
			     container, so `Anzeigen <span>` would make them two flex items
			     and the space between them would collapse to nothing. --}}
			<x-site.button type="submit" class="w-full sm:hidden" @click.prevent="apply()">
				<span x-text="count ? `Anzeigen (${count})` : 'Anzeigen'">Anzeigen{{ $matching ? ' ('.$matching.')' : '' }}</span>
			</x-site.button>

			{{-- **Always shown**, with nothing chosen as much as with something —
			     legacy renders it unconditionally and the live page confirms it.
			     An earlier pass hid it until a filter was set. --}}
			<x-site.button
				variant="outline"
				class="w-full"
				:href="$urlFor(array_fill_keys(\App\Support\CourseFilter::ATTRIBUTES, null))"
				@click.prevent="reset()"
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
	</form>
</div>
