@props(['hit'])

{{--
	One hit on the search's result page ([[15-search]]), **a row of the event
	list's grid** (`card/event.blade.php`, Marcel 2026-11-17: the page full
	width): the black rule and its spacing, then from `sm` three columns of
	twelve, 16/40 apart.

	- **4**: the title, bold, teal on hover.
	- **6**: the lead, grey.
	- **2**: what it is, the badge (*Angebot*, *Kurs*, *Lizenz* …), on the right.

	**No dates and prices** (Marcel, same day: tried in a third column, taken
	out); the lead gets the room. The header's panel draws the same row in
	Alpine (`layout/header.blade.php`): change the two together.

	On a phone the badge sits on the title's line, the lead under it. The
	whole row is the link.
--}}
<a href="{{ $hit->url }}" {{ $attributes->class(['group mt-16 block border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl']) }}>
	<span class="flex items-start justify-between gap-16 sm:grid sm:grid-cols-12 lg:gap-40">
		<span class="font-bold transition-colors group-hover:text-teal sm:col-span-4">{{ $hit->title }}</span>

		@if ($hit->lead)
			<span class="block text-gray-600 max-sm:hidden sm:col-span-6">{{ \Illuminate\Support\Str::limit($hit->lead, 240) }}</span>
		@endif

		<span class="shrink-0 sm:col-span-2 sm:col-start-11 sm:text-right"><x-ui.badge>{{ $hit->label }}</x-ui.badge></span>
	</span>

	@if ($hit->lead)
		<span class="mt-8 block text-gray-600 sm:hidden">{{ \Illuminate\Support\Str::limit($hit->lead, 240) }}</span>
	@endif
</a>
