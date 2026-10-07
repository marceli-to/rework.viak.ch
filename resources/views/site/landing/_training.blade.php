{{--
	The Firmenschulung teaser (the review's homepage marker 4), a second call
	to action beside the call band, which stays as it is (Marcel, 2026-10-07):
	the band is for whoever does not know yet, this for a company that does.
	The copy is the mockup's (`history/mockup/Homepage.html`, `.firm`), fixed
	here as the band's is, its heading split into the label and the line under
	it. *Firmenschulung anfragen* goes to the page's form (`#anfrage`), as the
	mockup's button does.

	A grey panel (Marcel picked it from four on 2026-10-07, after a framed card
	and an image beside the copy): the site's `gray-200` fill, so it reads as a
	block of its own without a second teal one under the band. The pitch and
	the button in six columns, the facts as label and value rows between
	grey hairlines in the other six, each fact on one line where the width
	allows (the label a fixed 80px), 16px in and 24 from lg. No image.

	Left out: the mockup's *Zuletzt geschult: Firmenname (…)*, a placeholder
	until VIAK names clients it may show (`Open-Questions.md` #40, which also
	asks for the price to be confirmed).
--}}
<section class="mt-48 bg-gray-200 p-16 sm:grid sm:grid-cols-12 sm:gap-16 lg:mt-64 lg:gap-40 lg:p-24">
	<div class="flex flex-col items-start sm:col-span-6">
		<p class="text-md font-bold text-teal lg:text-lg">Firmenschulung</p>
		<h2 class="mt-4 text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">Ein Kurs nur für dein Team</h2>
		<p class="mt-12 text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-xl">Wir bauen den Kurs um euer Projekt, eure Software und euren Stand. Bei euch im Büro oder in unseren Lofts in Zürich.</p>
		<x-ui.button href="{{ \App\Support\SiteUrl::training() }}#anfrage" class="mt-24 lg:mt-32">Firmenschulung anfragen</x-ui.button>
	</div>

	<dl class="mt-32 text-lg sm:col-span-6 sm:mt-0 lg:text-xl">
		@foreach ([
			'Format' => '1 bis 2 Tage am eigenen Projekt',
			'Gruppe' => '3 bis 12 Personen',
			'Ort' => 'Bei euch oder in Zürich',
			'Preis' => "Ab CHF 2'400 pro Tag, pauschal",
		] as $term => $fact)
			<div class="flex gap-16 border-t border-gray-400 py-12 last:border-b">
				<dt class="w-80 shrink-0 text-gray-600">{{ $term }}</dt>
				<dd class="leading-[1.3] font-bold text-pretty">{{ $fact }}</dd>
			</div>
		@endforeach
	</dl>
</section>
