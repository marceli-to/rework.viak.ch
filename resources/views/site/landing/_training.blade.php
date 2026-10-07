{{--
	The Firmenschulung teaser (the review's homepage marker 4), a second call
	to action beside the call band, which stays as it is (Marcel, 2026-10-07):
	the band is for whoever does not know yet, this for a company that does.
	The copy is the mockup's (`history/mockup/Homepage.html`, `.firm`), fixed
	here as the band's is, and it asks for nothing on the spot: *Firmenschulung
	anfragen* goes to the page's form (`#anfrage`), as the mockup's button does.

	Drawn in the site's pieces: the tiles' 1px teal frame and padding, the
	band's headline and lead sizes, the four facts each behind the 2px teal rule
	the mockup gives them, and the site's primary button. The image is the
	Firmenschulung page's, chosen in *Seiteninhalte → Firmenschulung*, beside
	the copy from sm (five columns of twelve, as the mockup's 0.9fr to 1.4fr)
	and above it on a phone; without one the copy takes the width.

	Left out: the mockup's *Zuletzt geschult: Firmenname (…)*, a placeholder
	until VIAK names clients it may show (`Open-Questions.md` #40, which also
	asks for the price to be confirmed).
--}}
<section class="mt-48 border border-teal sm:grid sm:grid-cols-12 lg:mt-64">
	{{-- 4:3 on a phone; from sm it covers its five columns at whatever height
	     the copy beside it takes. --}}
	@if ($trainingImage)
		<div class="sm:relative sm:col-span-5">
			<x-media.image
				:media="$trainingImage"
				ratio="4/3"
				sizes="(min-width: 1200px) 480px, (min-width: 700px) 40vw, 100vw"
				:max-width="1024"
				alt="Kurs in den Lofts der Visualisierungs-Akademie"
				class="block w-full object-cover sm:absolute sm:inset-0 sm:h-full"
			/>
		</div>
	@endif

	<div @class(['flex flex-col items-start p-8 lg:p-16', 'sm:col-span-7' => $trainingImage, 'sm:col-span-12' => ! $trainingImage])>
		<h2 class="text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">Firmenschulung: ein Kurs nur für dein Team</h2>
		<p class="mt-8 text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-2xl">
			Wir bauen den Kurs um euer Projekt, eure Software und euren Stand. Bei euch im Büro oder in unseren Lofts in Zürich.
		</p>

		<dl class="mt-16 grid w-full grid-cols-2 gap-16 text-lg leading-[1.4] lg:mt-24 lg:grid-cols-4 lg:text-2xl">
			@foreach ([
				'Format' => '1 bis 2 Tage am eigenen Projekt',
				'Gruppe' => '3 bis 12 Personen',
				'Ort' => 'Bei euch oder in Zürich',
				'Preis' => "Ab CHF 2'400 pro Tag, pauschal",
			] as $term => $fact)
				<div class="border-l-2 border-teal pl-8">
					<dt class="font-bold">{{ $term }}</dt>
					<dd>{{ $fact }}</dd>
				</div>
			@endforeach
		</dl>

		<x-ui.button href="{{ \App\Support\SiteUrl::training() }}#anfrage" class="mt-24 lg:mt-32">Firmenschulung anfragen</x-ui.button>
	</div>
</section>
