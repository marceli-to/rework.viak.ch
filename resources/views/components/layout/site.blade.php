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
	'keywords' => null,
	'image' => null,
	'canonical' => null,
])

@php
	$locale = app()->getLocale();

	/*
	 * The path without its locale prefix, so an alternate can swap the locale
	 * rather than gain a second one. `getPathInfo()` already starts `/de`, and
	 * prepending the locale again produced `/de/de` on every page.
	 */
	$path = \Illuminate\Support\Str::of(request()->getPathInfo())
		->after('/'.$locale)
		->start('/')
		->rtrim('/')
		->value();

	$seo = [
		'title' => $title ? $title.' • '.config('app.name') : config('app.name'),
		'description' => $description ?? config('site.seo.description'),
		'keywords' => $keywords ?? config('site.seo.keywords'),
		'image' => \App\Support\SiteUrl::canonical($image ?? config('site.seo.image')),
		'url' => \App\Support\SiteUrl::canonical($canonical ?? request()->getPathInfo()),
	];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" class="overflow-y-scroll">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	{{-- `page • Visualisierungs-Akademie`, as legacy composes it from
	     `config('seo.title')` — which is the short name, not the legal entity in
	     `APP_NAME`. Here they are the same string. --}}
	<title>{{ $seo['title'] }}</title>
	<meta name="description" content="{{ $seo['description'] }}">
	<meta name="keywords" content="{{ $seo['keywords'] }}">

	{{-- Neither of these exists on the live site. Legacy has no canonical
	     anywhere and serves `/` and `/de` with an identical title, which is
	     duplicate content ([[00-foundation]]). --}}
	<link rel="canonical" href="{{ $seo['url'] }}">
	@foreach (config('site.locales') as $alternate)
		<link rel="alternate" hreflang="{{ $alternate }}" href="{{ \App\Support\SiteUrl::canonical('/'.$alternate.$path) }}">
	@endforeach

	{{-- The full title, as legacy composes it — `og:title` and `<title>` carry
	     the same string there. --}}
	<meta property="og:title" content="{{ $seo['title'] }}">
	<meta property="og:description" content="{{ $seo['description'] }}">
	<meta property="og:url" content="{{ $seo['url'] }}">
	<meta property="og:image" content="{{ $seo['image'] }}">
	<meta property="og:site_name" content="{{ config('app.name') }}">

	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
	<link rel="manifest" href="/site.webmanifest">

	<meta name="theme-color" content="#ffffff">
	<meta name="msapplication-TileColor" content="#ffffff">

	{{-- Stops iOS turning course numbers like `07-120326` into phone links. --}}
	<meta name="format-detection" content="telephone=no">

	@vite(['resources/css/app.css', 'resources/js/site/site.js'])
</head>
{{-- The container and the body type, from `components/_blocks.scss` and
     `layout/_base.scss`.

     Measured against production: the body box is **1100px including its 16px
     padding**, so the content column is 1068px. An earlier pass read the 1100 as
     the content width and made the page 32px too wide.

     Legacy expressed the same thing as a max-width of `calc(100% - 16px)` at two
     breakpoints *plus* padding; one max-width and one padding says it once. --}}
<body class="mx-auto min-h-screen w-full max-w-[1100px] px-16 pb-16 text-lg leading-[1.3] tracking-[0.01em] antialiased sm:text-xl lg:text-3xl">
	<x-site.header :heading="$title" />

	{{-- `layout/_main.scss` --}}
	<main role="main" class="pb-24 sm:pb-32">
		{{ $slot }}
	</main>
</body>
</html>
