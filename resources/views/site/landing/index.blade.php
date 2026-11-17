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

	{{-- The course dates and, beside them, the news (Oliver, Roxy 2026-11-12):
	     half and half from `lg`, the news two side by side; under `lg` the
	     dates first and the news below them ("Kurstermine oben, News
	     rutschen runter"). Either alone takes the width. --}}
	@if ($events->isNotEmpty() || $news->isNotEmpty())
		<div class="mt-48 lg:mt-64 lg:grid lg:grid-cols-12 lg:gap-40">
			@if ($events->isNotEmpty())
				<div @class(['lg:col-span-6' => $news->isNotEmpty(), 'lg:col-span-12' => $news->isEmpty()])>
					@include('site.landing._events')
				</div>
			@endif
			@if ($news->isNotEmpty())
				<div @class(['max-lg:mt-48 lg:col-span-6' => $events->isNotEmpty(), 'lg:col-span-12' => $events->isEmpty()])>
					@include('site.landing._news')
				</div>
			@endif
		</div>
	@endif

	@include('site.landing._training')

	@if ($popular->isNotEmpty() || $featured->isNotEmpty())
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
