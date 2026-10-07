{{--
	*Warum bei der VIAK* (the review's homepage marker 9): the mockup's copy
	(`history/mockup/Homepage.html`, `.why`), fixed here as the teaser's is,
	and a button to *Über uns*. Drawn as the Firmenschulung teaser above it
	([[landing._training]]): the grey panel, the teal label over the heading.
	The experts the mockup lists under the copy are optional in the review and
	left out (Marcel, 2026-10-07); so is its image of the lofts, a stock
	render in the mockup, until VIAK has one of its own.
--}}
<section class="mt-48 bg-gray-200/50 p-16 sm:grid sm:grid-cols-12 sm:gap-16 lg:mt-64 lg:gap-40 lg:p-24">
	<div class="flex flex-col items-start sm:col-span-8">
		<p class="text-md font-bold text-teal lg:text-lg">Über uns</p>
		<h2 class="mt-4 text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">Warum bei der VIAK</h2>
		<div class="mt-12 text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-xl [&_p+p]:mt-12 lg:[&_p+p]:mt-16">
			<p>Die Visualisierungs-Akademie ist der Kursbetrieb von Nightnurse Images, einem der bekanntesten Studios für Architekturvisualisierung in der Schweiz. Unterrichtet wird in denselben Lofts, in denen täglich Bilder für Wettbewerbe und Immobilienprojekte entstehen.</p>
			<p>Seit der Zusammenführung mit 3D-Software.ch bekommst du hier beides: den Kurs und die Lizenz, von Menschen, die die Werkzeuge selbst einsetzen.</p>
		</div>
		<x-ui.button href="{{ \App\Support\SiteUrl::about() }}" class="mt-24 lg:mt-32">Mehr über uns</x-ui.button>
	</div>
</section>
