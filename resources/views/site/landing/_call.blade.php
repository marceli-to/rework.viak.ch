{{--
	The call band (the review's homepage marker 2, "Text statisch
	hinterlegen"): the mockup's copy, **fixed here and not an editable
	field**. The Kurse filter's teal box (`.card-teaser-training`,
	[[course.filter]]) at the page's width.

	Reworked 2026-10-07 (Marcel: it read flat), with no colour or size the
	site does not have:
	- **Two levels of copy.** The question is the headline, bold (Marcel),
	  at the tiles' 28px from lg and one step larger than them below lg (20,
	  then 24), or on a phone it is no bigger than the sentence under it.
	  The rest is regular weight below it.
	- **The number is the action**, at the headline's size. *Rückruf
	  vereinbaren* is a button, the site's `outline` one, which is white
	  with teal type and so stands out against the teal. It is the only
	  button in the band; the tiles above are links.
	- **The tiles' padding** (Marcel): 8px in, 16 from lg, the grid's own
	  gap, and the two halves centred on each other from sm.
--}}
<aside class="mt-48 bg-teal p-8 text-white sm:grid sm:grid-cols-12 sm:items-center sm:gap-16 lg:mt-64 lg:gap-40 lg:p-16">
	<div class="sm:col-span-8">
		<h2 class="text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">Nicht sicher, was du brauchst?</h2>
		<p class="mt-8 max-w-[34em] text-lg leading-[1.4] text-pretty lg:mt-16 lg:text-2xl">
			Ruf an und sprich mit jemandem, der die Tools täglich nutzt. Etwa zwanzig Minuten, kostenlos, unverbindlich.
		</p>
	</div>

	<div class="mt-24 flex flex-col items-start gap-16 sm:col-span-4 sm:mt-0 sm:items-end">
		<p class="text-2xl leading-[1.2] font-bold tabular-nums sm:text-3xl lg:text-4xl">
			<a href="tel:+41435014040" class="whitespace-nowrap hover:underline hover:decoration-2 hover:underline-offset-4">+41 43 501 40 40</a>
		</p>
		<x-ui.button variant="outline" href="{{ \App\Support\SiteUrl::contact() }}#nachricht">Rückruf vereinbaren</x-ui.button>
	</div>
</aside>
