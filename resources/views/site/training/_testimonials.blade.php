{{--
	*Kundenmeinungen* (the review's Firmenschulung marker 1): the testimonials
	picked for this page in the dashboard, in their order, published only
	([[Page]]). One row each, as Kontakt's blocks are made of: who said it in
	the aside, the quote in the column.
--}}
@foreach ($testimonials as $testimonial)
	<x-card.text>
		<x-slot:aside>
			<h2>{{ $testimonial->name }}</h2>
			@if ($context = $testimonial->getTranslation('context', app()->getLocale(), false))
				<p>{{ $context }}</p>
			@endif
		</x-slot:aside>
		<p>„{{ $testimonial->getTranslation('quote', app()->getLocale()) }}“</p>
	</x-card.text>
@endforeach
