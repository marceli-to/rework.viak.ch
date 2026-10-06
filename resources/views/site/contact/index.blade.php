{{--
	`web/pages/contact/index.blade.php`, rebuilt 1:1 ([[09-public-site]]).
	Measured against production on 2026-09-24.

	An `article.content-text` — *Get in touch* in the aside, the address and the
	map in the column, all of it bold teal — then two collapsibles: Anreise open,
	Impressum shut. The copy is legacy's, carried across verbatim from its
	partials into `_directions` and `_imprint` — save one fix: legacy's
	*Escher-Wyss-PlatzWer* is two sentences run together, and reads
	*Escher-Wyss-Platz. Wer* here (Marcel, 2026-09-24).

	**Legacy's Über uns and Team blocks moved to *Über uns*** (2026-10-06), as
	the 2026-09-23 review's Team mockup has them (`04-content.md`). Team had
	never rendered: legacy's `team_members` table is empty.

	The mockup review also approved a **contact form** for this page. Legacy has
	none; it waits on mail and on `Open-Questions.md` #24.
--}}
<x-layout.site title="Kontakt" heading="Kontakt">
	{{-- `section.container-contact`, 48px under it and 64 from `bp-md`. --}}
	<article class="mb-48 text-teal sm:grid sm:grid-cols-12 sm:gap-16 lg:mb-64 lg:gap-40">
		{{-- `xs:hide` — on a phone the header row already says Kontakt. --}}
		<aside class="max-sm:hidden sm:col-span-4">
			<h1 class="font-bold">Get in touch</h1>
		</aside>

		{{-- `.container-contact .text__body p` is bold teal, and so are its
		     `tel:` and `mailto:` links — no underline until hovered, then one
		     2px below the text. --}}
		<div class="mt-24 font-bold sm:col-span-8 sm:mt-0 [&_a]:no-underline [&_a]:underline-offset-2 [&_a:hover]:underline [&_p]:mb-12 lg:[&_p]:mb-16">
			<p>Visualisierungs-Akademie Schweiz GmbH<br>Limmatstrasse 291<br>CH-8005 Zürich<br>Schweiz</p>
			<p>
				<a href="tel:+41435014040" title="per Telefon">+41 43 501 40 40</a><br>
				<a href="mailto:hallo@visualisierungs-akademie.ch" title="per E-Mail">hallo@visualisierungs-akademie.ch</a>
			</p>

			<div class="mt-16 sm:mt-24 lg:mt-32">
				<x-ui.map />
			</div>
		</div>
	</article>

	<x-ui.collapsible title="Anreise">
		@include('site.contact._directions')
	</x-ui.collapsible>

	<x-ui.collapsible title="Impressum" :expanded="false" last>
		@include('site.contact._imprint')
	</x-ui.collapsible>
</x-layout.site>
