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
		['Warenkorb und Kasse', '/de/checkout/basket', 'Kurse und Software in einem Warenkorb, Anmeldung erst bei «Weiter», Software per Kreditkarte oder Rechnung'],
		['Profil', '/de/konto', 'Gebuchte Kurse, Dokumente, Bestellungen mit «Bezahlen»'],
		['Dashboard', '/dashboard', 'Kurse, Veranstaltungen, Kund:innen, Software, Bestellungen, Rechnungen, Seiteninhalte'],
		['Dashboard: Software', '/dashboard/software', 'Je Angebot seine Seiten «Angebot», «Was ist …?» und «Lizenzen» zum Bearbeiten, darunter die Produkte mit ihren Lizenzen'],
		['Firmenkurse', '/dashboard/firmenkurse', 'Kurs für eine Firma erfassen, Rechnung aus eigenen Positionen stellen und per E-Mail senden'],
	];

	$questions = [
		[
			'title' => 'Tracking: wer verwaltet den Tag Manager?',
			'go_live' => true,
			'text' => 'Die heutige Website misst Besuche und Werbung über den Google Tag Manager (GTM-M3L7WVP) mit Cookie-Banner. Verwaltet VIAK den Container und die Werbekonten selbst, oder eine Agentur?',
			'decides' => 'Wer die Anpassungen im Tag Manager macht und die Kampagnen-Links (UTM) einrichtet.',
		],
		[
			'title' => 'Tracking: welche Werbenetzwerke?',
			'go_live' => true,
			'text' => 'Heute Google Ads und Meta. Kommen LinkedIn, ChatGPT oder andere dazu?',
			'decides' => 'Welche Tags in den Tag Manager kommen.',
		],
		[
			'title' => '«Wie wurdest du auf uns aufmerksam?»',
			'go_live' => true,
			'text' => 'Welche Antworten sollen zur Auswahl stehen, und ist die Frage freiwillig? Gewünscht war ein Popup; wir empfehlen ein Feld in der Zusammenfassung der Kasse, das unterbricht den Kauf nicht.',
			'decides' => 'Die Frage im Kaufprozess und ihre Auswertung im Dashboard.',
		],
		[
			'title' => 'Software: Versand vor oder nach Zahlungseingang?',
			'go_live' => true,
			'text' => 'Software kann jetzt per Kreditkarte oder per Rechnung bezahlt werden, die Kund:in wählt in der Kasse. Mit der Rechnung ist eine unbezahlte Bestellung der Normalfall, dazu kommen abgebrochene Kartenzahlungen und Bestellungen, die das Team im Dashboard erfasst. Werden die Lizenzen sofort versendet oder erst nach Zahlungseingang? Die Bestätigung in der Kasse und die E-Mail sagen heute «nach Zahlungseingang».',
			'decides' => 'Ob die Versandliste im Dashboard unbezahlte Bestellungen zurückhält, und was Bestätigung und E-Mail versprechen.',
		],
		[
			'title' => 'Software: Zahlungsfrist der Rechnung?',
			'text' => 'Eine Software-Rechnung ist heute 10 Tage nach Rechnungsdatum fällig. Eine Kurs-Rechnung ist 10 Tage vor dem Kurs fällig (frühestens 10 Tage nach Rechnungsdatum), bei Software gibt es kein solches Datum. Bleibt es bei 10 Tagen, oder gilt für Software eine andere Frist?',
			'decides' => 'Das Fälligkeitsdatum auf der Software-Rechnung.',
		],
		[
			'title' => 'Software: Gutscheincodes und Studierendenpreise?',
			'text' => 'Gutscheincodes sollen auch für Software gelten (siehe «Noch nicht gebaut»). Zwei Punkte dazu: Die Software-Preise sind exkl. MWST. Zieht ein Gutschein über CHF 50 vom Preis exkl. MWST ab (die Kund:in spart dann CHF 54.05) oder vom Endbetrag? Und gilt ein fester Betrag bei Kursen und Software im selben Warenkorb zuerst für die Software, die sofort bezahlt wird? Ausserdem: Gibt es Preise für Studierende?',
			'decides' => 'Wie ein Gutschein auf Software angerechnet wird, und ob es eine Preisregel für Studierende braucht.',
		],
		[
			'title' => 'Bundles: mit eigenem Preis?',
			'text' => '«Das könnte dich auch interessieren» ist gebaut: VIAK wählt je Software und je Kurs im Dashboard passende Kurse und Software aus, sie erscheinen auf der Angebotsseite und im Warenkorb (Beispiel: Rhinoceros). Die Kund:innen stellen sich ihr Bundle so selbst zusammen. Soll ein selbst zusammengestelltes Bundle günstiger sein, oder genügen die Empfehlungen? Und sollen sie auch auf den Kursseiten erscheinen (etwa eine Rhino-Lizenz beim Rhino-Kurs)?',
			'decides' => 'Ob es eine Preisregel für Bundles braucht (wie bei den Gutscheincodes für Software) oder nur die Empfehlungen, und wo sie erscheinen.',
		],
		[
			'title' => 'EDU-Lizenzen: auf der Website oder nicht?',
			'text' => 'Die Antwort zu den EDU-Lizenzen war «öffentlich», und so sind sie jetzt auf der Website: Rhinoceros 9 EDU, Rhinoceros 9 EDU Upgrade und VisualARQ 3 Educational. In der Software-Liste vom 17. November stehen die drei aber weiterhin auf N (nicht sichtbar). Gilt die Antwort oder die Liste?',
			'decides' => 'Ob die drei EDU-Lizenzen auf der Lizenzseite stehen. Nichts, wenn sie bleiben.',
		],
		[
			'title' => 'Kundenmeinungen aus Google',
			'text' => 'Die Kundenmeinungen auf Startseite, Firmenschulung und Kursseiten sind gebaut und werden im Dashboard gepflegt. Unser Vorschlag: Wir übernehmen die Google-Bewertungen, die das Widget auf der heutigen Website zeigt, einmalig als Entwürfe (Zitat, Name und, wo es im Text steht, der Kurs). VIAK wählt im Dashboard aus, welche erscheinen. Eine laufende Verbindung zu Google empfehlen wir nicht: Die öffentliche Schnittstelle liefert nur die fünf neuesten Bewertungen, für alle braucht es Zugriff auf das Google-Unternehmensprofil von VIAK. Einmalig übernehmen, oder sollen neue Google-Bewertungen laufend dazukommen?',
			'decides' => 'Ob wir die Bewertungen einmal als Entwürfe übernehmen oder eine Verbindung zu Google einrichten.',
		],
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
		['Texte zur Software-Lieferung', 'Die Lieferung erfolgt von Hand durch das Team. Texte wie «Lieferung sofort per E-Mail» aus dem alten Shop sollten nicht übernommen werden.'],
	];

	$unbuilt = [
		['Tracking: Buchungen als Conversion', 'Laut Tag-Manager-Support (Call vom 2. November) müssen Werbe- und Analyse-Cookies nach der Zustimmung freigegeben werden. Das macht die neue Website bereits, die heutige kann es nicht. Offen bleibt: Google Ads zählt jedes abgeschickte Formular (Login, Registrierung, Newsletter, Kontakt und neu auch eine Buchung) pauschal als Conversion mit CHF 100. Damit nur Käufe mit ihrem echten Betrag zählen, meldet die Bestätigungsseite den Kauf an den Tag Manager, und dort wird die Conversion auf dieses Ereignis umgestellt. Der Plan steht, umgesetzt wird es später.', 'Zurückgestellt', false],
		['Tracking: Herkunft der Kund:innen', 'Tag Manager und Cookie-Banner sind gebaut, wie auf der heutigen Website, aber mit einer Kategorie für Marketing-Cookies, die bisher fehlte. Offen: die Herkunft jeder Buchung festhalten und die Frage «Wie wurdest du auf uns aufmerksam?».', 'Wartet auf die Tracking-Fragen', true],
		['Versand erst nach Zahlung', 'Die Versandliste hält unbezahlte Bestellungen zurück.', 'Wartet auf die Versand-Frage', false],
		['Gutscheincodes für Software', 'Je Code wählt VIAK, ob er für Kurse, für Software oder für beides gilt. Der Rabatt steht auf der Software-Rechnung, die Kreditkarte wird um denselben Betrag weniger belastet. Ein Code zählt pro Bestellung einmal, auch bei einer Bestellung nur mit Software. Die bestehenden Codes gelten weiter nur für Kurse.', 'Offen', false],
		['Mails zu Bestellungen aus dem Dashboard', 'Bestellungen aus dem Shop schicken Mails an Kund:in und Büro. Bestellungen, die das Team im Dashboard erfasst, schicken noch keine.', 'Offen', false],
	];

	$launch = [
		['Kreditkarte', 'Stripe-Schlüssel und Webhook im Stripe-Konto von VIAK einrichten. Im Prototyp läuft die Zahlung über eine Testseite, es wird nichts belastet.', true],
		['Buchhaltung', 'Run My Accounts: Kartenzahlungen werden dort verbucht. Bis zum Go-live wird nichts an die Buchhaltung geschickt.', false],
		['Mails', 'Im Prototyp gehen alle Mails an eine Testadresse, nie an Kund:innen.', false],
		['Newsletter', 'Die Anmeldungen gehen wie heute an Mailchimp. Im Prototyp werden sie nur aufgezeichnet.', false],
		['Daten', 'Ein frischer Export der heutigen Datenbank für den Umzug (der Prototyp arbeitet mit dem Stand vom 11. September 2026), danach Kontrolle der übernommenen Kurse, Buchungen und Rechnungen.', false],
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
			<x-ui.badge>{{ count($questions) }} Fragen an VIAK</x-ui.badge>
			<x-ui.badge variant="danger">{{ collect($questions)->where('go_live', true)->count() }} davon vor dem Go-live</x-ui.badge>
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
			<p class="mt-8">Was VIAK im Dashboard selbst pflegt. Die Funktionen sind fertig, es fehlen die Texte und Bilder.</p>
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
