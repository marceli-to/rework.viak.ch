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
{{-- `overflow-y-scroll`: the scrollbar is always there, so a short page and
     a long one put the content column in the same place. Without it the page
     shifts sideways as you navigate. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-y-scroll">
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

	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<meta name="apple-mobile-web-app-title" content="Visualisierungs-Akademie">
	<link rel="manifest" href="/site.webmanifest">

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
