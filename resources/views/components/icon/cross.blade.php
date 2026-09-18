@props(['size' => 'medium'])

@if ($size === 'large')
	<svg {{ $attributes->merge(['class' => 'w-31', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31 30">
		<path fill="currentColor" d="M28 0L15.3 12.6 2.6 0 0 2.6l12.8 12.7-12 11.9L3.4 30l11.9-12 11.9 12 2.7-2.8-12-11.9L30.7 2.6z"/>
	</svg>
@elseif ($size === 'medium')
	<svg {{ $attributes->merge(['class' => 'w-22', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22">
		<path fill="currentColor" d="M20.116 0L11 9.01 1.886 0 0 1.886 9.166 11l-8.59 8.538L2.41 21.53 11 12.939l8.54 8.591 1.886-1.992L12.834 11 22 1.886z"/>
	</svg>
@endif
