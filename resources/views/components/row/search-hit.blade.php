@props(['hit'])

{{--
	One hit on the search's result page ([[15-search]]), **a row of the event
	list's grid** (`card/event.blade.php`, Marcel 2026-11-17: the page full
	width): the black rule and its spacing, then from `sm` three columns of
	twelve, 16/40 apart.

	- **4**: the title, bold, teal on hover, and under it what it is, the
	  badge (*Angebot*, *Kurs*, *Lizenz* …).
	- **5**: the lead, grey.
	- **3**: its dates and prices, one a line ([[SearchFacts]]), right-aligned
	  as the event row's fee.

	On a phone the three stack. The whole row is the link. The header's panel
	draws its hits on its own, in its narrower column.
--}}
<a href="{{ $hit->url }}" {{ $attributes->class(['group mt-16 block border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl']) }}>
	<span class="block sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<span class="block sm:col-span-4">
			<span class="block font-bold transition-colors group-hover:text-teal">{{ $hit->title }}</span>
			<x-ui.badge class="mt-8">{{ $hit->label }}</x-ui.badge>
		</span>

		{{-- No lead (a licence of one line, Rhino.io): the column stays empty, the facts keep theirs. --}}
		@if ($hit->lead)
			<span class="mt-8 block text-gray-600 sm:col-span-5 sm:mt-0">{{ \Illuminate\Support\Str::limit($hit->lead, 200) }}</span>
		@endif

		@if (! empty($hit->facts))
			<span class="mt-8 block tabular-nums sm:col-span-3 sm:col-start-10 sm:mt-0 sm:text-right">
				@foreach ($hit->facts as $fact)
					<span class="block">{{ $fact }}</span>
				@endforeach
			</span>
		@endif
	</span>
</a>
