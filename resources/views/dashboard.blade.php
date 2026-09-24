<!DOCTYPE html>
{{--
	The dashboard's SPA shell ([[07-dashboard]]) — legacy's
	`web.layout.backend`, which is the site's own frame: the teal `<html>` of the
	signed-in screens and the white 1100px column, with
	`components/layout/site.blade.php`'s body classes copied here rather than
	shared, because the SPA draws everything inside it
	([[viak-dashboard-looks-like-the-site]]).
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-y-scroll bg-teal">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	{{-- The admin's name for the shell's corner; the SPA has no other way to
	     know who is signed in without asking. --}}
	<meta name="admin-name" content="{{ auth()->user()?->first_name }}">
	<title>Dashboard • {{ config('app.name') }}</title>
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<meta name="apple-mobile-web-app-title" content="Visualisierungs-Akademie">
	<link rel="manifest" href="/site.webmanifest">

	@vite(['resources/css/app.css', 'resources/js/app/app.js'])
</head>
<body class="mx-auto min-h-screen w-full max-w-[calc(100%-16px)] bg-white px-8 pb-16 text-lg leading-[1.3] tracking-[0.01em] antialiased sm:max-w-[calc(100%-32px)] sm:px-16 sm:text-xl lg:max-w-[1100px] lg:text-3xl">
	<div id="app"></div>
</body>
</html>
