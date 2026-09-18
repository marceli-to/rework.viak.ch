<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-y-scroll">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name') }}</title>
	<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<meta name="apple-mobile-web-app-title" content="Visualisierungs-Akademie">
	<link rel="manifest" href="/site.webmanifest">

	@vite(['resources/css/app.css', 'resources/js/app/app.js'])
</head>
<body class="bg-gray-200 font-sans text-black antialiased">
	<div id="app"></div>
</body>
</html>
