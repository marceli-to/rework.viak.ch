@props(['src' => null])

{{--
	**An Angebot's logo** (Oliver, 2026-11-17, [[14-angebote]] *Logos*): the
	file as it was uploaded ([[Software::logoUrl]]), whole in a square, never
	cropped. The size is the caller's (`class="size-40"`). Decorative: the
	title beside it names the Angebot, so the alt text is empty.

	No `src`, nothing at all: an Angebot without a logo shows none, and
	nothing stands in for one (Marcel, same day).
--}}
@if ($src)
	<img src="{{ $src }}" alt="" width="250" height="250" loading="lazy" {{ $attributes->class(['block shrink-0 object-contain']) }}>
@endif
