<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $title ?? config('app.name') }}</title>
	@vite(['resources/css/app.css', 'resources/js/site/site.js'])
</head>
<body class="bg-white font-sans text-text antialiased">
	{{ $slot }}
</body>
</html>
