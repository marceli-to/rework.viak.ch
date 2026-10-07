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
