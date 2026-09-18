@php($nav = [
	'Kurse' => \App\Support\SiteUrl::courses(),
	'Software' => '#',
	'Firmenschulung' => '#',
	'Über uns' => '#',
	'Kontakt' => '#',
])
<header class="sticky top-0 z-50 bg-white">
	<div class="mx-auto flex max-w-[1140px] items-center justify-between gap-5 border-b border-line px-5 py-3.5 md:px-10">
		<a href="/" class="text-lg font-semibold tracking-tight text-ink">VIAK</a>

		<nav class="hidden gap-6 text-sm font-medium text-text md:flex">
			@foreach ($nav as $label => $href)
				<a href="{{ $href }}" class="hover:text-teal-dark">{{ $label }}</a>
			@endforeach
		</nav>

		<div class="flex items-center gap-3.5 text-text">
			<a href="#" aria-label="Warenkorb" class="hover:text-teal-dark">&#128722;</a>
			<a href="#" aria-label="Konto" class="hover:text-teal-dark">&#128100;</a>
		</div>
	</div>
</header>
