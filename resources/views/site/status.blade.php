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
		['Startseite', '/de', 'Slider, Vorhaben, nächste Kurstermine, Firmenschulung, beliebte Angebote, Kundenmeinungen, Über uns, Newsletter'],
		['Kurse', '/de/kurse', 'Liste mit Filter, von dort jede Kursseite'],
		['Software', '/de/software', 'Liste mit Filter. Rhinoceros hat Beispielinhalte, die übrigen Seiten warten auf Texte und Bilder'],
		['Eine Softwareseite', '/de/software/rhinoceros', 'Lizenzen mit Stückzahl und Hostsoftware, In den Warenkorb'],
		['Vorhaben', '/de/vorhaben/raume-visualisieren', 'Beispiel «Räume visualisieren», Texte sind Platzhalter'],
		['Firmenschulung', '/de/firmenschulung', 'Text, Anfrageformular, Kundenmeinungen'],
		['Über uns', '/de/ueber-uns', 'Text, Expert:innen, Team'],
		['Kontakt', '/de/kontakt', 'Adresse, Karte, Kontaktformular'],
		['Warenkorb und Kasse', '/de/checkout/basket', 'Kurse und Software in einem Warenkorb, Anmeldung erst bei «Weiter», Software per Kreditkarte'],
		['Profil', '/de/konto', 'Gebuchte Kurse, Dokumente, Bestellungen mit «Bezahlen»'],
		['Dashboard', '/dashboard', 'Kurse, Veranstaltungen, Kund:innen, Software, Bestellungen, Rechnungen, Seiteninhalte'],
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
			'text' => 'Im Shop wird Software sofort per Kreditkarte bezahlt. Unbezahlt bleibt eine Bestellung, wenn die Zahlung abgebrochen wird oder wenn das Team sie im Dashboard erfasst. Sollen solche Bestellungen erst nach Zahlungseingang versendet werden?',
			'decides' => 'Ob die Versandliste im Dashboard unbezahlte Bestellungen zurückhält.',
		],
		[
			'title' => 'Software: Gutscheincodes und Studierendenpreise?',
			'text' => 'Gutscheincodes gelten heute nur für Kurse. Sollen sie auch für Software gelten, und gibt es Preise für Studierende?',
			'decides' => 'Die Preisregeln im Shop.',
		],
		[
			'title' => 'Bundles: mit eigenem Preis?',
			'text' => 'Statt fester Bundles zeigt «Das könnte dich auch interessieren» passende Software und Kurse, die VIAK selbst auswählt; die Kund:innen stellen sich ihr Bundle so selbst zusammen. Soll ein selbst zusammengestelltes Bundle günstiger sein, oder genügen die Empfehlungen? Und sollen sie auch auf den Kursseiten erscheinen (etwa eine Rhino-Lizenz beim Rhino-Kurs), oder nur auf den Software-Seiten und im Warenkorb?',
			'decides' => 'Ob es eine Preisregel für Bundles braucht (wie bei den Gutscheincodes für Software) oder nur die Empfehlungen, und wo sie erscheinen.',
		],
		[
			'title' => 'Newsletter an Mailchimp?',
			'text' => 'Die Anmeldung im Fuss der Startseite wird im Moment nur aufgezeichnet. Soll sie wie heute an Mailchimp gehen?',
			'decides' => 'Ob die neue Website eine Mailchimp-Anbindung braucht.',
		],
		[
			'title' => 'Blog und Aktuelles',
			'text' => 'Im Mockup-Review war Aktuelles als Einstieg in einen Blog vorgesehen, der Blog selbst war offen. Gibt es einen Blog, mit eigenen Seiten?',
			'decides' => 'Ob Beiträge gebaut werden, und das letzte offene Element der Startseite.',
		],
		[
			'title' => 'Hauptnavigation',
			'text' => 'Im Mockup-Review stand «Angebot» an Stelle von «Kurse» und «Software». Im Prototyp sind Kurse und Software getrennte Menüpunkte. Passt das so?',
			'decides' => 'Die Navigation auf jeder Seite.',
		],
		[
			'title' => 'Firmenschulung auf der Startseite',
			'text' => 'Ist «Ab CHF 2\'400 pro Tag, pauschal» der richtige Preis? Und welche Kunden dürfen unter «Zuletzt geschult» genannt werden? Bis dahin bleibt diese Zeile weg.',
			'decides' => 'Den Text des Firmenschulungs-Hinweises.',
		],
		[
			'title' => 'Welche Vorhaben gibt es?',
			'text' => 'Die Mockups zeigen sechs (Räume, Bilder, Objekte, Bewegtbild, KI, Teams), besprochen wurde nur Räume. Vorhaben werden im Dashboard angelegt; die Texte im Prototyp sind Platzhalter.',
			'decides' => 'Nur Inhalte, keine Entwicklung.',
		],
		[
			'title' => 'Englische Version',
			'text' => 'Die Website ist deutsch, die Adressen behalten das /de/. Wird es je eine englische Version geben?',
			'decides' => 'Nichts im Moment. Ein klares Nein würde einiges vereinfachen.',
		],
		[
			'title' => 'Alte Fälligkeitsdaten der Rechnungen',
			'text' => 'Die alte Website hat das Fälligkeitsdatum bei jeder Änderung einer Rechnung überschrieben, bei den 541 bezahlten Rechnungen ist es verloren. Wird es noch gebraucht (Mahnwesen, Buchhaltung)?',
			'decides' => 'Ob wir es beim Umzug aus Run My Accounts oder den PDFs wiederherstellen.',
		],
		[
			'title' => 'Ein Kurstermin mit falschem Datum',
			'text' => 'Ein Termin «Blender Modeling» fällt nach Korrektur der Jahreszahl auf einen Samstag, der Kurs lief aber immer Donnerstag und Freitag. Nie gebucht, nie auf der Website. Korrigieren oder streichen?',
			'decides' => 'Nur die Kurshistorie.',
		],
		[
			'title' => 'Rechnung 000419 über CHF −149.00',
			'text' => 'Ein Gutschein über CHF 648 wurde 2023 auf einen Kurs zu CHF 499 angewendet, die Rechnung steht seither offen mit einem negativen Betrag. Die neue Website lässt das nicht mehr zu, übernimmt die Rechnung aber wie sie ist.',
			'decides' => 'Ob die Rechnung vor dem Umzug in der Buchhaltung bereinigt wird.',
		],
	];

	$content = [
		['Software-Seiten', 'Texte, Bilder und Kategorien je Software. Nur Rhinoceros hat Beispielinhalte.'],
		['Vorhaben', 'Die echten Vorhaben mit Text, Bild und den passenden Kursen und Software.'],
		['Beliebte Angebote', 'Kurse und Software mit «Beliebt» markieren, im Prototyp sind einige zum Zeigen markiert.'],
		['Texte zur Software-Lieferung', 'Die Lieferung erfolgt von Hand durch das Team. Texte wie «Lieferung sofort per E-Mail» aus dem alten Shop sollten nicht übernommen werden.'],
	];

	$unbuilt = [
		['Tracking: Kauf-Events und Herkunft', 'Tag Manager und Cookie-Banner sind gebaut, wie auf der heutigen Website, aber mit einer Kategorie für Marketing-Cookies, die bisher fehlte. Offen: Käufe mit Betrag an Google Ads und Meta melden, die Herkunft jeder Buchung festhalten und die Frage «Wie wurdest du auf uns aufmerksam?».', 'Wartet auf die Tracking-Fragen', true],
		['Blog und Aktuelles', 'Beiträge und ihre Seiten, das letzte Element der Startseite.', 'Wartet auf die Blog-Frage', false],
		['Newsletter-Anbindung', 'Die Anmeldung an Mailchimp übergeben.', 'Wartet auf die Mailchimp-Frage', false],
		['Versand erst nach Zahlung', 'Die Versandliste hält unbezahlte Bestellungen zurück.', 'Wartet auf die Versand-Frage', false],
		['«Das könnte dich auch interessieren»', 'Empfehlungen, die VIAK je Software und Kurs im Dashboard auswählt, unter der Software-Seite und im Warenkorb. Ersetzt die festen Bundles.', 'Wartet auf die Bundle-Frage', false],
		['Mails zu Bestellungen aus dem Dashboard', 'Bestellungen aus dem Shop schicken Mails an Kund:in und Büro. Bestellungen, die das Team im Dashboard erfasst, schicken noch keine.', 'Offen', false],
	];

	$launch = [
		['Kreditkarte', 'Stripe-Schlüssel und Webhook im Stripe-Konto von VIAK einrichten. Im Prototyp läuft die Zahlung über eine Testseite, es wird nichts belastet.', true],
		['Buchhaltung', 'Run My Accounts: Kartenzahlungen werden dort verbucht. Bis zum Go-live wird nichts an die Buchhaltung geschickt.', false],
		['Mails', 'Im Prototyp gehen alle Mails an eine Testadresse, nie an Kund:innen.', false],
		['Daten', 'Ein frischer Export der heutigen Datenbank für den Umzug (der Prototyp arbeitet mit dem Stand vom 11. September 2026), danach Kontrolle der übernommenen Kurse, Buchungen und Rechnungen.', false],
		['Mietcomputer', 'Beim Umzug prüfen, ob gebuchte Mietcomputer die verfügbare Anzahl eines Termins übersteigen.', false],
		['Weiterleitungen', 'Alle heutigen Adressen leiten auf die neuen um, damit Suchmaschinen und Lesezeichen weiter funktionieren.', false],
		['Server', 'PHP-Version, Hintergrundprozesse für Mails und PDFs, Upload-Grösse, Spamschutz des Kontaktformulars für die echte Domain.', false],
		['Kursunterlagen', 'Die hochgeladenen Kursunterlagen in einen geschützten Speicher verschieben.', false],
	];
@endphp

<x-layout.site title="Projektstand" heading="Projektstand" noindex>
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Projektstand</h1>
			<div class="mt-8 text-gray-600">Stand 20. Oktober 2026</div>
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
			<p class="mt-8">Was wir von VIAK brauchen. Die ersten drei braucht es vor dem Go-live, die übrigen halten nichts auf.</p>
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
