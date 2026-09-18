@props(['items' => []])
<nav class="mx-auto max-w-[1140px] px-5 pt-6 text-xs text-gray-400 lg:px-10">
	@foreach ($items as $label => $href)
		@if ($href && ! $loop->last)
			<a href="{{ $href }}" class="hover:text-teal">{{ $label }}</a>
			<span class="mx-1.5">/</span>
		@else
			<span class="text-black">{{ $label }}</span>
		@endif
	@endforeach
</nav>
