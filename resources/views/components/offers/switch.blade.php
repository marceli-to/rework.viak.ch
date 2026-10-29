@props(['current'])

{{--
	*Alle Angebote · Kurse · Software* ([[14-angebote]], Marcel 2026-10-29):
	the three views of the catalogue, on top of each. **The only way into
	Kurse and Software from the menu**, which says *Angebote* alone, so it is
	drawn as navigation, the menu's bold type a size down, and not as a quiet
	filter. The open one teal, as the menu marks its page.
--}}
@php
	$views = [
		'offers' => ['Alle Angebote', \App\Support\SiteUrl::offers()],
		'courses' => ['Kurse', \App\Support\SiteUrl::courses()],
		'licences' => ['Software', \App\Support\SiteUrl::softwareIndex()],
	];
@endphp

<nav {{ $attributes->class(['mb-32 lg:mb-48']) }} aria-label="Angebote">
	<ul class="flex flex-wrap gap-x-24 gap-y-8 text-lg font-bold sm:text-xl lg:gap-x-48 lg:text-2xl">
		@foreach ($views as $key => [$label, $href])
			<li>
				<a href="{{ $href }}"
					@if ($key === $current) aria-current="page" @endif
					@class(['transition-colors', 'text-teal' => $key === $current, 'hover:text-teal' => $key !== $current])>
					{{ $label }}
				</a>
			</li>
		@endforeach
	</ul>
</nav>
