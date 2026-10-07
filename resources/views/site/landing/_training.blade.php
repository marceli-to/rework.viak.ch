{{--
	The Firmenschulung teaser (the review's homepage marker 4), a second call
	to action beside the call band, which stays as it is (Marcel, 2026-10-07):
	the band is for whoever does not know yet, this for a company that does.
	The copy is the mockup's (`history/mockup/Homepage.html`, `.firm`), fixed
	here as the band's is, its heading split into the label and the line under
	it. *Firmenschulung anfragen* goes to the page's form (`#anfrage`), as the
	mockup's button does.

	Redrawn 2026-10-07 (Marcel did not like the framed card): no frame and no
	teal bars, so it does not compete with the teal band above it. The page's
	own grid instead, 16px and 40 from lg, the image in six columns and the
	copy in six. The label is bold teal as the site's asides are, the line
	under it the band's headline size; the four facts are a 2x2 list between
	grey hairlines, the term small and grey, the fact black. The image is the
	Firmenschulung page's, chosen in *Seiteninhalte → Firmenschulung*;
	without one the copy keeps its column, the intro's span-8 from column 5.

	Left out: the mockup's *Zuletzt geschult: Firmenname (…)*, a placeholder
	until VIAK names clients it may show (`Open-Questions.md` #40, which also
	asks for the price to be confirmed).
--}}
<section class="mt-48 grid grid-cols-12 gap-x-16 gap-y-24 lg:mt-64 lg:gap-x-40">
	@if ($trainingImage)
		<div class="col-span-12 sm:col-span-6">
			<x-media.image
				:media="$trainingImage"
				ratio="4/3"
				sizes="(min-width: 1200px) 564px, (min-width: 700px) 50vw, 100vw"
				:max-width="1200"
				alt=""
				class="block w-full"
			/>
		</div>
	@endif

	<div @class([
		'col-span-12 flex flex-col items-start justify-center',
		'sm:col-span-6' => $trainingImage,
		'sm:col-span-8 sm:col-start-5' => ! $trainingImage,
	])>
		<p class="font-bold text-teal">Firmenschulung</p>
		<h2 class="mt-8 text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:mt-12 lg:text-4xl">Ein Kurs nur für dein Team</h2>
		<p class="mt-12 text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-xl">
			Wir bauen den Kurs um euer Projekt, eure Software und euren Stand. Bei euch im Büro oder in unseren Lofts in Zürich.
		</p>

		<dl class="mt-24 grid w-full grid-cols-2 gap-x-16 lg:mt-32 lg:gap-x-40">
			@foreach ([
				'Format' => '1 bis 2 Tage am eigenen Projekt',
				'Gruppe' => '3 bis 12 Personen',
				'Ort' => 'Bei euch oder in Zürich',
				'Preis' => "Ab CHF 2'400 pro Tag, pauschal",
			] as $term => $fact)
				<div class="border-t border-gray-400 py-12">
					<dt class="text-sm text-gray-600 lg:text-md">{{ $term }}</dt>
					<dd class="mt-4 text-lg leading-[1.3] text-pretty lg:text-xl">{{ $fact }}</dd>
				</div>
			@endforeach
		</dl>

		<x-ui.button href="{{ \App\Support\SiteUrl::training() }}#anfrage" class="mt-24 lg:mt-32">Firmenschulung anfragen</x-ui.button>
	</div>
</section>
