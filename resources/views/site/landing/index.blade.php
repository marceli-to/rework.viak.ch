{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home" :image="$og ? '/storage/uploads/'.$og->file : null">
	@include('site.landing._intro')

	@if ($projects->isNotEmpty())
		@include('site.landing._projects')
	@endif

	@include('site.landing._call')

	@if ($events->isNotEmpty())
		<x-ui.collapsible title="Nächste Kurstermine" class="mt-48 lg:mt-64" last>
			@include('site.landing._events')
		</x-ui.collapsible>
	@endif

	@include('site.landing._training')

	@if ($featured->isNotEmpty())
		@include('site.landing._featured')
	@endif

	@include('site.landing._about')

	@if ($testimonials->isNotEmpty())
		{{-- Marker 10, picked in *Seiteninhalte → Startseite*: Firmenschulung's
		     cards in its grid. --}}
		<x-ui.collapsible title="Kundenmeinungen" class="mt-48 lg:mt-64" last>
			@include('site.training._testimonials')
		</x-ui.collapsible>
	@endif

	<x-slot:footer>
		@include('site.landing._footer')
	</x-slot:footer>
</x-layout.site>
