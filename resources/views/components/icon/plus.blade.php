@props(['size' => 'large'])

{{-- Ported from the Vue set, which had it first (`resources/css/README.md`) —
     the checkout's *Adresse erfassen* link is the public site's first use.
     Legacy's three sizes, kept: 20, 16 and the 12 the link uses. --}}
@if ($size === 'large')
	<svg {{ $attributes->merge(['class' => 'w-20', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
		<path fill="currentColor" d="M20 9h-9V0H9v9H0v2h9v9h2v-9h9z"/>
	</svg>
@elseif ($size === 'tiny')
	<svg {{ $attributes->merge(['class' => 'w-12', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12">
		<path fill="currentColor" d="M12 5.4H6.6V0H5.4V5.4H0V6.6H5.4V12H6.6V6.6H12V5.4Z"/>
	</svg>
@else
	<svg {{ $attributes->merge(['class' => 'w-16', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
		<path fill="currentColor" d="M16 7.2H8.8V0H7.2v7.2H0v1.6h7.2V16h1.6V8.8H16V7.2z"/>
	</svg>
@endif
