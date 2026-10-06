{{--
	*Kundenmeinungen* (the review's Firmenschulung marker 1): the testimonials
	picked for this page in the dashboard, in their order, published only
	([[Page]]). Cards in a grid, with no collapsible and no heading around them
	(Marcel, 2026-10-06): the grid is *Über uns*'s, three across from lg.
--}}
<div class="grid grid-cols-12 gap-16 lg:gap-40">
	@foreach ($testimonials as $testimonial)
		<x-card.testimonial :testimonial="$testimonial" class="col-span-12 sm:col-span-6 lg:col-span-4" />
	@endforeach
</div>
