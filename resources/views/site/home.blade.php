{{-- "Home • Visualisierungs-Akademie", as the live site titles it. --}}
<x-layout.site title="Home" :image="$og ? '/storage/uploads/'.$og->file : null">
	{{--
		Legacy's intro (`web/pages/home/index.blade.php`), rebuilt 1:1: an
		`article.content-text-media is-reverse` (`layout/_article.scss`), all of
		it teal. *Ihre Zukunft ist visuell* in the span-4 aside, the copy bold in
		the span-8 column, then the hero images as a slider.

		`is-reverse` puts the text **above** the images from sm, 48px between
		them (`mb-12x`); on a phone the source order stands, the images first
		and 24px under them (`mb-6x`). The copy is legacy's, from its `__()`
		strings, verbatim. The images are legacy's home hero, kept on
		`Page::for('home')` and edited in *Seiteninhalte → Startseite*.
	--}}
	<article class="text-teal sm:flex sm:flex-col">
		@if ($slides->isNotEmpty())
			<x-media.slider :images="$slides" alt="Visualisierungs-Akademie" class="mb-24 sm:order-2 sm:mb-0" />
		@endif

		<div class="sm:order-1 sm:mb-48 sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<aside class="mb-12 sm:col-span-4">
				<h1 class="font-bold">Ihre Zukunft ist visuell</h1>
			</aside>

			<div class="font-bold sm:col-span-8 [&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0">
				<p>Visualisieren ist die Schlüsselkompetenz der Zukunft.<br>Wir machen Sie fit: zum Beispiel in unseren vielseitigen Kursen oder massgeschneiderten Individualschulungen.</p>
				<p>Wir sind führend, wenn es um Visualisierung geht.</p>
			</div>
		</div>
	</article>

	{{--
		*Was möchtest du machen?* (the review's homepage marker 1): the
		published Vorhaben, in the order the dashboard dragged them into, each a
		tile to its page. Drawn with the course card's frame and heading
		(`components/cards/_teaser.scss`): a 1px teal border, the title teal at
		the card's sizes, the tile's line below it as the card's category label
		is drawn above. On hover it fills teal, as the card's overlay does.
	--}}
	@if ($projects->isNotEmpty())
		<section class="mt-48 lg:mt-64">
			<h2 class="mb-16 font-bold lg:mb-24">Was möchtest du machen?</h2>

			<div class="grid grid-cols-12 gap-16 lg:gap-40">
				@foreach ($projects as $project)
					<a
						href="{{ \App\Support\SiteUrl::project($project->getTranslation('slug', app()->getLocale())) }}"
						class="group col-span-6 block border border-teal p-8 text-black transition-colors duration-[120ms] ease-in-out hover:bg-teal hover:text-white sm:col-span-4 lg:p-16"
					>
						<h3 class="text-lg leading-[1.2] break-words hyphens-auto text-teal group-hover:text-white sm:text-2xl lg:text-4xl">
							{{ $project->getTranslation('title', app()->getLocale()) }}
						</h3>

						@if ($teaser = $project->getTranslation('teaser', app()->getLocale(), false))
							<p class="mt-8 text-xxs leading-[1.3] font-medium text-gray-600 group-hover:text-white sm:text-sm lg:text-lg">{{ $teaser }}</p>
						@endif
					</a>
				@endforeach
			</div>
		</section>
	@endif

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

	{{--
		*Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
		Veranstaltungen, nobody picks them ([[HomeController]]). An open
		collapsible, as the course page lists its dates, and each one the
		portal's row (`x-row.event`), which leads with the course: the title
		links to the course, the dates, where and with whom, the fee. No course
		number and no state badge, which are the dashboard's. *Anmelden* goes to
		the course page, where booking already knows about full courses, an
		existing seat and the laptop question.
	--}}
	@if ($events->isNotEmpty())
		<x-ui.collapsible title="Nächste Kurstermine" class="mt-48 lg:mt-64" last>
			@foreach ($events as $event)
				<x-row.event :event="$event" :numbered="false" :show-state="false">
					<x-slot:action>
						<x-ui.button href="{{ \App\Support\SiteUrl::course($event->course->getTranslation('slug', app()->getLocale())) }}">Anmelden</x-ui.button>
					</x-slot:action>
				</x-row.event>
			@endforeach

			<a href="{{ \App\Support\SiteUrl::courses() }}" class="mt-32 inline-flex items-center gap-8 text-teal hover:underline">
				Alle Kurse und Termine
				<x-icon.arrow-right />
			</a>
		</x-ui.collapsible>
	@endif

	{{--
		Legacy's footer (`web/partials/footer.blade.php`, `layout/_footer.scss`),
		which only the homepage has: a 1px black rule, 32px above it and 48
		from lg, 16px inside and 28 from sm, everything 14px and 16 from sm,
		the headings bold and grey, links underlined 2px below on hover.
		*Newsletter* in span-7, *Kontakt* in span-4 from column 9.

		The newsletter is legacy's `frontend/newsletter/Index.vue` without Vue:
		the line and *Abonnieren* (`.btn-subscribe`), which opens the form;
		Vorname and Nachname side by side, E-Mail, *Abonnieren* again, bold and
		grey as legacy's in-form one is. A refused signup comes back open.
		**It reaches no Mailchimp list** until `Open-Questions.md` #13 is
		answered ([[NewsletterController]]).
	--}}
	<x-slot:footer>
		<footer class="mt-32 border-t border-black py-16 text-md sm:py-28 sm:text-lg lg:mt-48 [&_a:hover]:underline [&_a:hover]:underline-offset-2 [&_h2]:mb-12 [&_h2]:font-bold [&_h2]:text-gray-600 [&_p]:mb-12 [&_p:last-child]:mb-0">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div id="newsletter" class="scroll-mt-16 sm:col-span-7" x-data="{ open: @js($errors->hasAny(['firstname', 'name', 'email', 'cf-turnstile-response'])) }">
					<h2>Newsletter</h2>

					@if (session('newsletter') === 'sent')
						<p>Wir haben Deine Anmeldung erhalten, vielen Dank.</p>
					@else
						<p>Regelmässig über neue Kurse und Angebote informiert werden:</p>

						<button type="button" x-show="! open" @click="open = true" title="Newsletter Formular anzeigen" class="flex items-center gap-4 py-8 leading-none hover:text-teal sm:gap-8">
							<x-icon.arrow-right class="shrink-0" />
							<span>Abonnieren</span>
						</button>

						<form method="POST" action="{{ \App\Support\SiteUrl::newsletter() }}" class="mt-32 lg:[&_label]:text-lg" x-show="open" x-cloak>
							@csrf

							<div class="sm:grid sm:grid-cols-12 sm:gap-16">
								<div class="sm:col-span-6">
									<x-form.field name="firstname" label="Vorname" required autocomplete="given-name" />
								</div>
								<div class="sm:col-span-6">
									<x-form.field name="name" label="Nachname" required autocomplete="family-name" />
								</div>
							</div>
							<x-form.field name="email" label="E-Mail" type="email" required autocomplete="email" />

							{{-- The honeypot, as on Kontakt. --}}
							<div class="absolute -left-[9999px]" aria-hidden="true">
								<label for="website">Website</label>
								<input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
							</div>

							<x-form.turnstile action="newsletter" />

							<button type="submit" title="Newsletter abonnieren" class="flex items-center gap-4 py-12 leading-none font-bold text-gray-600 hover:text-teal sm:gap-8 sm:py-16">
								<x-icon.arrow-right class="shrink-0" />
								<span>Abonnieren</span>
							</button>
						</form>
					@endif
				</div>

				<div class="mt-24 sm:col-span-4 sm:col-start-9 sm:mt-0">
					<h2>Kontakt</h2>
					<p>Visualisierungs-Akademie Schweiz GmbH<br>Limmatstrasse 291<br>CH-8005 Zürich</p>
					<p>
						<a href="tel:+41435014040" title="per Telefon">+41 43 501 40 40</a><br>
						<a href="mailto:hallo@visualisierungs-akademie.ch" title="per E-Mail">hallo@visualisierungs-akademie.ch</a>
					</p>
					<div class="flex gap-12">
						<a href="https://www.instagram.com/viak.ch/" target="_blank" rel="noopener" title="Visualisierungs-Akademie auf Instagram">Instagram</a>
						<a href="https://www.facebook.com/ViAkSchweiz" target="_blank" rel="noopener" title="Visualisierungs-Akademie auf Facebook">Facebook</a>
					</div>
				</div>
			</div>
		</footer>
	</x-slot:footer>
</x-layout.site>
