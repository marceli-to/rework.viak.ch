{{--
	*Warum bei der VIAK* (the review's homepage marker 9): a heading and text
	edited in *Seiteninhalte → Startseite: Über uns* (Marcel, 2026-10-07), the
	mockup's copy (`history/mockup/Homepage.html`, `.why`) until then
	([[HomeAboutSchema]]), and a button to *Über uns*. Drawn as the
	Firmenschulung teaser above it ([[landing._training]]): the grey panel,
	the teal label over the heading. The image beside the copy, as the
	mockup has it, is the page's first (or the one marked *Vorschau*) at 16:9,
	the dashboard cropper's shape;
	without one the copy keeps its eight columns. The experts the mockup lists
	under the copy are optional in the review and left out (Marcel).
--}}
<section class="mt-48 bg-gray-200/50 p-16 sm:grid sm:grid-cols-12 sm:gap-16 lg:mt-64 lg:gap-40 lg:p-24">
	<div @class(['flex flex-col items-start', 'sm:col-span-6' => $aboutImage, 'sm:col-span-8' => ! $aboutImage])>
		<p class="text-md font-bold text-teal lg:text-lg">Über uns</p>
		<h2 class="mt-4 text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">{{ $about['title'] }}</h2>
		<div class="mt-12 text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-xl">
			<x-ui.rich-text :html="$about['text']" />
		</div>
		<x-ui.button href="{{ \App\Support\SiteUrl::about() }}" class="mt-24 lg:mt-32">Mehr über uns</x-ui.button>
	</div>

	@if ($aboutImage)
		<figure class="mt-24 sm:col-span-6 sm:mt-0 sm:self-center">
			<x-media.image
				:media="$aboutImage"
				ratio="16/9"
				sizes="(min-width: 1132px) 540px, (min-width: 700px) 50vw, 100vw"
				:max-width="1200"
				class="block w-full"
			/>
		</figure>
	@endif
</section>
