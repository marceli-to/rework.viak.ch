{{--
	*Projektstand*, `/de/projektstand` (2026-10-20): what is still open when
	the client reviews the prototype, in the site's own look. Each part a plain
	heading and lead, full width (Marcel, 2026-10-20: no grid, no number), then
	rows on a black rule, the title in `span-4` and the text in `span-8`.

	The content is `.rework/Open-Questions.md`, the chunk docs' open points
	and `Todo.md`'s cutover list, rewritten for the client. Not linked, not
	in the sitemap, `noindex`. Remove it before the launch.
--}}
@php
	$review = [
		['Über uns', '/de/ueber-uns', 'Text, Expert:innen, Team'],
		['Kontakt', '/de/kontakt', 'Adresse, Karte, Kontaktformular'],
		['Warenkorb und Kasse', '/de/checkout/basket', 'Kurse und Software in einem Warenkorb, Anmeldung erst bei «Weiter», Software per Kreditkarte, TWINT oder Rechnung'],
		['Profil', '/de/konto', 'Gebuchte Kurse, Dokumente, Bestellungen mit «Bezahlen»'],
		['Dashboard', '/dashboard', 'Kurse, Veranstaltungen, Kund:innen, Software, Bestellungen, Rechnungen, Seiteninhalte'],
		['Dashboard: Software', '/dashboard/software', 'Je Angebot seine Seiten «Angebot», «Was ist …?» und «Lizenzen» zum Bearbeiten, darunter die Produkte mit ihren Lizenzen'],
		['Mietlizenzen, die bald auslaufen', '/dashboard/bestellungen', 'Unter Bestellungen: jede Jahresmietlizenz, deren Jahr in den nächsten 60 Tagen endet oder in den letzten 30 Tagen geendet hat (Bestelldatum plus ein Jahr), bis die Kund:in sie wieder bestellt. Je Lizenz «Erinnerung senden»: eine E-Mail mit dem Ablaufdatum und einem Link zum Verlängern'],
		['Firmenkurse', '/dashboard/firmenkurse', 'Kurs für eine Firma erfassen, Rechnung aus eigenen Positionen stellen und per E-Mail senden'],
	];

	$questions = [
		[
			'title' => 'KI-Zugang zu den Daten',
			'text' => 'Die Website hat einen Zugang für KI-Assistenten (MCP-Server), heute nur lesend: Kurstermine, Buchungen, offene Rechnungen, offene Lizenzbestellungen. Christoph richtet ihn in Claude für die ganze Firma ein. Jede Person meldet sich dann einmal mit ihrem eigenen VIAK-Konto an, Claude arbeitet in ihrem Namen, und jeder Aufruf wird protokolliert. Unser Vorschlag: nur Admins erhalten Zugang. Was soll der Zugang können? Welche Fragen soll er beantworten, und darf er auch etwas ändern (z.B. eine Lizenz als versendet markieren, eine Buchung erfassen)? Am einfachsten eine Liste mit Beispielen aus dem Alltag im Büro.',
			'decides' => 'Welche Werkzeuge der Zugang bekommt, und wer ihn nutzen darf.',
		],
	];

	$content = [
		['Angebotsseiten', 'Die Texte sind aus den Webseitentext-Dokumenten übernommen. Es fehlen die Bilder (alle Angebote ausser Rhinoceros) und die Texte für Lands Design und Rhino.io. Gepflegt im Dashboard unter Software.'],
		['Lizenzen', 'Je Lizenz ein Hinweis unter dem Titel, wo es einen braucht (schreibt Oliver), und je Angebot die Systemvoraussetzungen. In den Dokumenten sind beide noch Platzhalter, auf der Website fehlen sie darum noch überall.'],
		['Neue Kurse', 'ComfyUI, SketchUp & D5 Render, Figma Weave, Rendering-Finish mit Photoshop & KI und Enscape & Veras sind mit den Texten erfasst, aber nicht publiziert, mit Kursgebühr 0. Vor dem Publizieren: Gebühr, Kursleitung, Niveau, Sprache, Bilder und Daten.'],
		['Vorhaben', 'Die Boxen «Was möchtest du machen?» auf der Startseite sind die Vorhaben und zeigen noch die alten Titel und Zeilen («Räume visualisieren» usw.). Bitte im Dashboard unter Vorhaben umbenennen und die Texte auf die Kategorien anpassen, wie in deiner Roxy-Liste (z.B. «Architektur & Interior Design: V-Ray, Enscape, Twinmotion»), dazu je Vorhaben Text, Bild und die passenden Kurse und Software.'],
		['Beliebte Angebote', 'Angebote und Kurse mit «Beliebt» markieren, im Prototyp sind einige zum Zeigen markiert.'],
		['Kundenmeinungen', 'VIAK erfasst die Kundenmeinungen von Hand im Dashboard, auch die aus Google. Es gibt keine Übernahme und keine Verbindung zu Google.'],
		['«Wie wurdest du auf uns aufmerksam?»', 'Die Frage ist gebaut, mit Platzhalter-Antworten. VIAK schickt uns die Antworten, die zur Auswahl stehen sollen.'],
		['Tag Manager und Werbenetzwerke', 'VIAK verwaltet den Google Tag Manager und die Werbekonten selbst und entscheidet, welche Werbenetzwerke dazukommen.'],
		['Texte zur Software-Lieferung', 'Die Lieferung erfolgt von Hand durch das Team. Texte wie «Lieferung sofort per E-Mail» aus dem alten Shop sollten nicht übernommen werden.'],
	];

	$unbuilt = [
		['Tracking: Buchungen als Conversion', 'Laut Tag-Manager-Support (Call vom 2. November) müssen Werbe- und Analyse-Cookies nach der Zustimmung freigegeben werden. Das macht die neue Website bereits, die heutige kann es nicht. Offen bleibt: Google Ads zählt jedes abgeschickte Formular (Login, Registrierung, Newsletter, Kontakt und neu auch eine Buchung) pauschal als Conversion mit CHF 100. Damit nur Käufe mit ihrem echten Betrag zählen, meldet die Bestätigungsseite den Kauf an den Tag Manager, und dort wird die Conversion auf dieses Ereignis umgestellt. Der Plan steht, umgesetzt wird es später.', 'Zurückgestellt', false],
		['Tracking: Herkunft der Kund:innen', 'Tag Manager und Cookie-Banner sind gebaut, wie auf der heutigen Website, aber mit einer Kategorie für Marketing-Cookies, die bisher fehlte. Die Frage «Wie wurdest du auf uns aufmerksam?» steht auf der Bestätigungsseite, mit Platzhalter-Antworten. Offen: die Herkunft jeder Buchung festhalten und die Auswertung im Dashboard.', 'Offen', true],
		['Gutscheincodes für Software', 'Je Code wählt VIAK, ob er für Kurse, für Software oder für beides gilt. Der Rabatt wird vom Preis exkl. MWST abgezogen und steht auf der Software-Rechnung, die Kreditkarte wird um denselben Betrag weniger belastet. Ein Code zählt pro Bestellung einmal, auch bei einer Bestellung nur mit Software. Die bestehenden Codes gelten weiter nur für Kurse.', 'Offen', false],
		['Mails zu Bestellungen aus dem Dashboard', 'Bestellungen aus dem Shop schicken Mails an Kund:in und Büro. Bestellungen, die das Team im Dashboard erfasst, schicken noch keine.', 'Offen', false],
	];

	$launch = [
		['Kreditkarte und TWINT', 'Die neue Website zahlt über das bestehende Stripe-Konto von VIAK, mit demselben Schlüssel wie heute. Neu im Live-Modus des Kontos: TWINT einschalten und einen Webhook einrichten (die heutige Website hat keinen), das machen wir. Auf der Testumgebung läuft beides schon im Testmodus desselben Kontos, es wird nichts belastet.', false],
		['Buchhaltung', 'Run My Accounts: Kartenzahlungen werden dort verbucht. Bis zum Go-live wird nichts an die Buchhaltung geschickt.', false],
		['Mails', 'Im Prototyp gehen alle Mails an eine Testadresse, nie an Kund:innen.', false],
		['Newsletter', 'Die Anmeldungen gehen wie heute an Mailchimp. Im Prototyp werden sie nur aufgezeichnet.', false],
		['Daten', 'Ein frischer Export der heutigen Datenbank für den Umzug (der Prototyp arbeitet mit dem Stand vom 11. September 2026), danach Kontrolle der übernommenen Kurse, Buchungen und Rechnungen. Am selben Tag ein Export des Shops 3d-software.ch: seine Bestellungen und Kundinnen und Kunden kommen mit (bestehende Konten werden über die E-Mail-Adresse zusammengeführt), dazu eine Liste der Bestellungen, deren Status wir angenommen haben.', false],
		['Mietcomputer', 'Beim Umzug prüfen, ob gebuchte Mietcomputer die verfügbare Anzahl eines Termins übersteigen.', false],
		['Weiterleitungen', 'Alle heutigen Adressen, auch die von 3d-software.ch, leiten auf die neuen um, damit Suchmaschinen und Lesezeichen weiter funktionieren.', false],
		['Server', 'PHP-Version, Hintergrundprozesse für Mails und PDFs, Upload-Grösse, Spamschutz des Kontaktformulars für die echte Domain.', false],
		['Kursunterlagen', 'Die hochgeladenen Kursunterlagen in einen geschützten Speicher verschieben.', false],
	];
@endphp

<x-layout.site title="Projektstand" heading="Projektstand" noindex>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Projektstand</h1>
			<div class="mt-8 text-gray-600">Stand 25. November 2026</div>
		</x-slot:aside>

		<p>Die neue Website ist fast fertig. Hier steht, was noch offen ist: was wir anschauen möchten, auf welche Antworten wir warten, was noch nicht gebaut ist und was vor dem Go-live zu tun bleibt.</p>

		<div class="mt-16 flex flex-wrap gap-8">
			<x-ui.badge>{{ count($questions) }} {{ count($questions) === 1 ? 'Frage' : 'Fragen' }} an VIAK</x-ui.badge>
			@if (collect($questions)->where('go_live', true)->isNotEmpty())
				<x-ui.badge variant="danger">{{ collect($questions)->where('go_live', true)->count() }} davon vor dem Go-live</x-ui.badge>
			@endif
			<x-ui.badge>{{ count($unbuilt) }} noch nicht gebaut</x-ui.badge>
		</div>
	</x-layout.article>

	{{-- 1. What to look at --}}
	<section class="mt-48 lg:mt-64">
		<header class="mb-8 sm:text-lg lg:text-xl">
			<h2 class="font-bold">Bitte anschauen</h2>
			<p class="mt-8">Die Seiten des Prototyps. Zahlungen laufen über eine Testseite, Mails gehen an eine Testadresse: es kann gefahrlos bestellt und gebucht werden. Die Zugangsdaten folgen separat.</p>
		</header>

		@foreach ($review as [$title, $url, $text])
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<a href="{{ $url }}" target="_blank" class="font-bold underline decoration-1 underline-offset-[3px] hover:text-teal">{{ $title }}</a>
					</div>
					<div class="sm:col-span-8">{{ $text }}</div>
				</div>
			</div>
		@endforeach
	</section>

	{{-- 2. Questions for the client --}}
	<section class="mt-48 lg:mt-64">
		<header class="mb-8 sm:text-lg lg:text-xl">
			<h2 class="font-bold">Fragen an VIAK</h2>
			<p class="mt-8">Was wir von VIAK brauchen. Die markierten braucht es vor dem Go-live, die übrigen halten nichts auf.</p>
		</header>

		@foreach ($questions as $i => $question)
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<strong class="font-bold">{{ $i + 1 }}. {{ $question['title'] }}</strong>
						@if ($question['go_live'] ?? false)
							<div class="mt-4"><x-ui.badge variant="danger">Vor dem Go-live</x-ui.badge></div>
						@endif
					</div>
					<div class="max-sm:mt-8 sm:col-span-8">
						<p>{{ $question['text'] }}</p>
						<p class="mt-8 text-gray-600">Entscheidet: {{ $question['decides'] }}</p>
					</div>
				</div>
			</div>
		@endforeach
	</section>

	{{-- 3. Content VIAK enters --}}
	<section class="mt-48 lg:mt-64">
		<header class="mb-8 sm:text-lg lg:text-xl">
			<h2 class="font-bold">Inhalte</h2>
			<p class="mt-8">Was VIAK selbst pflegt oder uns liefert. Die Funktionen sind fertig, es fehlen die Texte, Bilder und Antworten.</p>
		</header>

		@foreach ($content as [$title, $text])
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4"><strong class="font-bold">{{ $title }}</strong></div>
					<div class="max-sm:mt-8 sm:col-span-8">{{ $text }}</div>
				</div>
			</div>
		@endforeach
	</section>

	{{-- 4. Not built yet --}}
	<section class="mt-48 lg:mt-64">
		<header class="mb-8 sm:text-lg lg:text-xl">
			<h2 class="font-bold">Noch nicht gebaut</h2>
			<p class="mt-8">Das meiste davon wartet auf eine der Fragen oben.</p>
		</header>

		@foreach ($unbuilt as [$title, $text, $state, $goLive])
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<strong class="font-bold">{{ $title }}</strong>
						<div class="mt-4 flex flex-wrap gap-8">
							<x-ui.badge :variant="$state === 'Offen' ? 'neutral' : 'warning'">{{ $state }}</x-ui.badge>
							@if ($goLive)
								<x-ui.badge variant="danger">Vor dem Go-live</x-ui.badge>
							@endif
						</div>
					</div>
					<div class="max-sm:mt-8 sm:col-span-8">{{ $text }}</div>
				</div>
			</div>
		@endforeach
	</section>

	{{-- 5. Before the launch --}}
	<section class="mt-48 lg:mt-64">
		<header class="mb-8 sm:text-lg lg:text-xl">
			<h2 class="font-bold">Vor dem Go-live</h2>
			<p class="mt-8">Was wir beim Umschalten erledigen. Wo wir etwas von VIAK brauchen, ist es markiert.</p>
		</header>

		@foreach ($launch as [$title, $text, $needsViak])
			<div class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-4">
						<strong class="font-bold">{{ $title }}</strong>
						@if ($needsViak)
							<div class="mt-4"><x-ui.badge variant="warning">Braucht VIAK</x-ui.badge></div>
						@endif
					</div>
					<div class="max-sm:mt-8 sm:col-span-8">{{ $text }}</div>
				</div>
			</div>
		@endforeach
	</section>
</x-layout.site>
