@props(['hit'])

{{--
	One search hit ([[15-search]]): in the header's suggestions and on the
	result page. The row of the site's lists, a black rule above it; the title
	bold, what it is as a badge on the right, the lead under it in grey.
--}}
<a href="{{ $hit->url }}" {{ $attributes->class(['group block border-t border-black pt-8 pb-16 sm:pt-12']) }}>
	<span class="flex items-start justify-between gap-16">
		<span class="font-bold group-hover:text-teal">{{ $hit->title }}</span>
		<x-ui.badge class="mt-2 shrink-0">{{ $hit->label }}</x-ui.badge>
	</span>
	@if ($hit->lead)
		<span class="mt-4 block text-md text-gray-600 sm:text-lg lg:text-xl">{{ \Illuminate\Support\Str::limit($hit->lead, 140) }}</span>
	@endif
</a>
