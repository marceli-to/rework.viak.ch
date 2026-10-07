@props(['testimonials'])

{{--
	The picked testimonials as a slider (Marcel, 2026-10-07), on Firmenschulung
	and the homepage: the quote cards ([[card.testimonial]]) three across from
	lg, two from sm, one on a phone, at the grid's gaps (16px, 40 from lg).
	Swiper, as the intro's slider is ([[testimonial-slider]]): it pages on by
	itself, and the dots under it, no arrows, page by hand. The cards of a
	page are as tall as the tallest. With no more cards than fit, there is
	nothing to page and the dots are hidden.
--}}
<div {{ $attributes }} x-data="testimonialSlider">
	<div class="swiper" x-ref="track">
		<div class="swiper-wrapper">
			@foreach ($testimonials as $testimonial)
				{{-- `h-auto!` over Swiper's `height: 100%`, so the flex row
				     stretches each card to the tallest. --}}
				<div class="swiper-slide h-auto!">
					<x-card.testimonial :testimonial="$testimonial" class="h-full" />
				</div>
			@endforeach
		</div>
	</div>

	{{-- Swiper adds a `button.dot` per page, `.is-active` the current one. --}}
	<div x-ref="dots" class="mt-16 flex justify-center gap-8 lg:mt-24 [&>button]:size-10 [&>button]:cursor-pointer [&>button]:rounded-full [&>button]:bg-gray-400 [&>button]:transition-colors [&>button.is-active]:bg-teal"></div>
</div>
