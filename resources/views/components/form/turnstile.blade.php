@props(['action', 'siteKey' => config('services.turnstile.site_key')])

{{--
	Cloudflare Turnstile ([[Turnstile]]): the widget and its error, in the form
	group's spacing ([[form.field]]). **It renders only with a site key**, as
	the map does with its own; without one the server skips the check too.
	German, light, and it posts its token as `cf-turnstile-response`. `action`
	names the form, and the server requires the token to carry it.

	**The widget is invisible** (set on the widget in Cloudflare, 2026-10-06),
	so it takes no room and has no group margin; only its error does. That
	mode obliges the Datenschutzerklärung to cite Cloudflare's Turnstile
	Privacy Addendum, which `site/contact/_imprint` does under 11.1.
--}}
@if ($siteKey)
	<div class="relative">
		<div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-action="{{ $action }}" data-language="de" data-theme="light"></div>

		@error('cf-turnstile-response')
			<div class="mb-16 text-md text-danger lg:mb-32 lg:text-lg">{{ $message }}</div>
		@enderror
	</div>

	@once
		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
	@endonce
@endif
