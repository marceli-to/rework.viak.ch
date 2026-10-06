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

	**The contact form is new** (the 2026-09-23 review's Kontakt marker 2; legacy
	has none), built 2026-10-06 as a second block drawn like the first:
	*Nachricht senden* in the aside, the mockup's three fields in the column.
	Where it sends and why nothing is stored is [[ContactController]].
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

	{{-- The form, as a second block like the first. `#nachricht` is where a
	     sent or refused form lands, so the answer is on screen. --}}
	<article id="nachricht" class="mb-48 scroll-mt-16 sm:grid sm:grid-cols-12 sm:gap-16 lg:mb-64 lg:gap-40">
		<aside class="text-teal sm:col-span-4">
			<h2 class="font-bold">Nachricht senden</h2>
		</aside>

		<div class="mt-24 sm:col-span-8 sm:mt-0">
			@if (session('contact') === 'sent')
				<x-ui.toast variant="success">Danke, deine Nachricht ist bei uns angekommen.</x-ui.toast>
			@elseif ($errors->any())
				<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
			@endif

			<form method="POST" action="{{ \App\Support\SiteUrl::contact() }}">
				@csrf

				<x-form.field name="name" label="Name" required autocomplete="name" :value="auth()->user()?->name" />
				<x-form.field name="email" label="E-Mail" type="email" required autocomplete="email" :value="auth()->user()?->email" />
				<x-form.textarea name="message" label="Nachricht" required />

				{{-- The honeypot ([[ContactController]]): off screen, out of the
				     tab order, and hidden from screen readers, so only a bot
				     fills it in. --}}
				<div class="absolute -left-[9999px]" aria-hidden="true">
					<label for="website">Website</label>
					<input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
				</div>

				<x-ui.button type="submit">Nachricht senden</x-ui.button>
			</form>
		</div>
	</article>

	<x-ui.collapsible title="Anreise">
		@include('site.contact._directions')
	</x-ui.collapsible>

	<x-ui.collapsible title="Impressum" :expanded="false" last>
		@include('site.contact._imprint')
	</x-ui.collapsible>
</x-layout.site>
