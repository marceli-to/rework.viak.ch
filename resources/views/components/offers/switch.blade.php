@props(['current'])

{{--
	*Alle Angebote · Kurse · Software* ([[14-angebote]]): the three views of
	the catalogue, on Kurse and Software **at the top of the filter column,
	drawn as the filter's category rows** (Marcel, 2026-10-29): a grey rule
	under each, 40px high, the open one bold, as a chosen category is. It
	first stood as a bold row over the page; *Alle Angebote* has none and
	links the two in its text instead.

	Inside the filter's panel, so on a phone it shows with the filter; the
	phone menu has Kurse and Software rows of their own.
--}}
@php
	$views = [
		'offers' => ['Alle Angebote', \App\Support\SiteUrl::offers()],
		'courses' => ['Kurse', \App\Support\SiteUrl::courses()],
		'licences' => ['Software', \App\Support\SiteUrl::softwareIndex()],
	];
@endphp

<nav {{ $attributes->class(['mb-40']) }} aria-label="Angebote">
	<ul class="border-t border-gray-400">
		@foreach ($views as $key => [$label, $href])
			<li class="flex min-h-40 items-center border-b border-gray-400">
				<a href="{{ $href }}"
					@if ($key === $current) aria-current="page" @endif
					@class(['block w-full text-lg hover:text-teal', 'font-bold' => $key === $current])>
					{{ $label }}
				</a>
			</li>
		@endforeach
	</ul>
</nav>
