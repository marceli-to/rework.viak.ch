{{--
	The public site's shell ([[00-foundation]]).

	**The current design, rebuilt on the new stack.** Blade and Alpine instead of
	Blade and Vue, Tailwind instead of 6.5k lines of SCSS — and the same page.
	New pages and new features come after parity, so nothing here is an
	improvement on the design; where a value looks arbitrary it is legacy's.

	`body` carries the container, exactly as `layout/_base.scss` does, so the
	whole document is one column and sections do not each re-centre themselves.

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
{{-- `layout/_base.scss`: p-4, sm:pt-7 (overridden to 0), 16/18/24px, 1.3, 0.01em --}}
<body class="inner-block relative min-h-screen p-4 pt-0 text-base leading-[1.3] tracking-[0.01em] text-ink antialiased sm:text-lg md:text-2xl">
	<x-site.header :heading="$title" />

	{{-- `layout/_main.scss`: pb-6, sm:pb-8 --}}
	<main role="main" class="pb-6 sm:pb-8">
		{{ $slot }}
	</main>

	<x-site.footer />
</body>
</html>
