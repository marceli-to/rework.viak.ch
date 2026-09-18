{{--
	The public site's shell ([[00-foundation]]).

	Blade and Alpine — no Vue on these pages at all. What it adds over legacy's
	`head.blade.php`, all of it measured against production on 2026-09-18:

	- a **canonical** tag. Legacy has none anywhere, and serves `/` and `/de`
	  with an identical <title>, which is duplicate content on the live site.
	- **hreflang**, which costs nothing and matters the day EN ships.
	- no second Typekit kit. Legacy loads `kcs4ept` (neuzeit-grotesk) alongside
	  `bmx5jih`, referenced by no stylesheet — a dead render-blocking request on
	  every page.
--}}
@props([
	'title' => null,
	'description' => null,
	'canonical' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
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
<body class="flex min-h-screen flex-col bg-white font-sans text-text antialiased">
	<x-site.header />

	<main class="flex-1">
		{{ $slot }}
	</main>

	<x-site.footer />
</body>
</html>
