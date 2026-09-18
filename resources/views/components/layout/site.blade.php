{{--
	The public site's shell ([[00-foundation]]).

	**The current design, rebuilt on the new stack** — Blade and Alpine instead
	of Blade and Vue, Tailwind instead of 6.5k lines of SCSS, and the same page.
	Where a value looks arbitrary it is legacy's, and the source file it came
	from is named in a comment.

	`body` carries the container, exactly as `layout/_base.scss` does, so the
	whole document is one column and sections do not each re-centre themselves.

	**There is no footer.** The live site has none on any page except the
	homepage, where `web/partials/footer.blade.php` renders a newsletter form
	and an address block — and that is homepage content, not a site chrome
	element. An earlier pass added one to every page; it was invented.

	Three things legacy's `head.blade.php` does not have, all measured against
	production on 2026-09-18: a **canonical** tag (it has none anywhere, and
	serves `/` and `/de` with an identical title), **hreflang**, and only one
	Typekit kit — legacy loads `kcs4ept` (neuzeit-grotesk) alongside `bmx5jih`
	and no stylesheet references it.
--}}
@props([
	'title' => null,
	'description' => null,
	'canonical' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ $title ? $title.' • '.config('app.name') : config('app.name') }}</title>
	<meta name="description" content="{{ $description }}">

	<link rel="canonical" href="{{ \App\Support\SiteUrl::canonical($canonical ?? request()->getPathInfo()) }}">
	@foreach (config('site.locales') as $locale)
		<link rel="alternate" hreflang="{{ $locale }}" href="{{ \App\Support\SiteUrl::canonical('/'.$locale.request()->getPathInfo()) }}">
	@endforeach

	<meta property="og:title" content="{{ $title ?? config('app.name') }}">
	<meta property="og:description" content="{{ $description }}">
	<meta property="og:url" content="{{ \App\Support\SiteUrl::canonical(request()->getPathInfo()) }}">
	<meta property="og:site_name" content="{{ config('app.name') }}">

	@vite(['resources/css/app.css', 'resources/js/site/site.js'])
</head>
{{-- `components/_blocks.scss` (the container) and `layout/_base.scss` (padding,
     type, colour) written out rather than hidden behind a class. --}}
<body class="relative mx-auto min-h-screen max-w-[calc(100%-16px)] p-16 pt-0 text-lg leading-[1.3] tracking-[0.01em] text-black antialiased sm:max-w-[calc(100%-32px)] sm:text-xl lg:max-w-[1100px] lg:text-3xl">
	<x-site.header :heading="$title" />

	{{-- `layout/_main.scss` --}}
	<main role="main" class="pb-24 sm:pb-32">
		{{ $slot }}
	</main>
</body>
</html>
