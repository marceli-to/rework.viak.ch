@props(['siteKey' => config('services.turnstile.site_key')])

{{--
	Cloudflare Turnstile ([[Turnstile]]): the widget and its error, in the form
	group's spacing ([[form.field]]). **It renders only with a site key**, as
	the map does with its own; without one the server skips the check too.
	German, light, and it posts its token as `cf-turnstile-response`.
--}}
@if ($siteKey)
	<div class="relative mb-16 lg:mb-32">
		<div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-language="de" data-theme="light"></div>

		@error('cf-turnstile-response')
			<div class="pt-8 text-md text-danger lg:text-lg">{{ $message }}</div>
		@enderror
	</div>

	@once
		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
	@endonce
@endif
