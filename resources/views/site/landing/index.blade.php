{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home" :image="$og ? '/storage/uploads/'.$og->file : null">
	@include('site.landing._intro')

	{{-- The Vorhaben tiles carry the call band beside them; without tiles
	     the band stands alone. --}}
	@if ($projects->isNotEmpty())
		@include('site.landing._projects')
	@else
		@include('site.landing._call')
	@endif

	@if ($events->isNotEmpty())
		@include('site.landing._events')
	@endif

	@include('site.landing._training')

	@if ($featured->isNotEmpty() || $featuredSoftware->isNotEmpty())
		@include('site.landing._featured')
	@endif

	@include('site.landing._about')

	@if ($testimonials->isNotEmpty())
		{{-- Marker 10, picked in *Seiteninhalte → Startseite*: Firmenschulung's
		     slider, under a heading as the other sections have. --}}
		<section class="mt-48 lg:mt-64">
			<h2 class="mb-16 font-bold lg:mb-24">Kundenmeinungen</h2>
			<x-testimonial.slider :testimonials="$testimonials" />
		</section>
	@endif

	<x-slot:footer>
		@include('site.landing._footer')
	</x-slot:footer>
</x-layout.site>
