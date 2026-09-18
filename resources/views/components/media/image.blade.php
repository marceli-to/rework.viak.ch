<picture>
	@foreach($sources as $source)
		<source
			srcset="{{ $source['srcset'] }}"
			type="{{ $source['type'] }}"
			sizes="{{ $source['sizes'] }}"
			@isset($source['media']) media="{{ $source['media'] }}" @endisset
		>
	@endforeach

	{{-- width and height are always present so the page does not shift as
	     images load; the ratio comes from the crop where there is one. --}}
	<img
		src="{{ $fallbackUrl }}"
		alt="{{ $alt }}"
		width="{{ $width }}"
		height="{{ $height }}"
		loading="{{ $loading }}"
		decoding="async"
		@class([$class => $class !== ''])
		{{ $attributes }}
	>
</picture>
