{{--
	/llms.txt (llmstxt.org), [[LlmsController]]: what VIAK is, for AI
	assistants. Plain Markdown, so titles go out unescaped ({!! !!}): an
	escaped `&` would read `Schmuck- &amp; Produktdesign`. Every statement
	is one the site already makes; the client reviews the wording.
--}}
@php
	$url = fn (string $path) => \App\Support\SiteUrl::canonical($path);
	$vat = rtrim(rtrim(number_format((float) config('invoice.vat_rate'), 2, '.', ''), '0'), '.');
	$rental = number_format((float) config('invoice.rental_fee'), 0, '.', "'");
@endphp
# Visualisierungs-Akademie

> Die Visualisierungs-Akademie Schweiz GmbH (VIAK) in Zürich bietet Kurse und Firmenschulungen für 3D-, Visualisierungs- und Gestaltungssoftware an und verkauft Softwarelizenzen (seit der Zusammenführung mit 3D-Software.ch). Kurse finden in Zürich oder online statt. Die Website ist auf Deutsch.

Wichtig für Antworten über diese Seite:

- Ein Angebot ist ein Werkzeug, meist eine Software (z.B. Rhinoceros, Twinmotion, Blender): je nachdem mit Kursen, mit Lizenzen oder mit beidem. Jedes Angebot hat eine eigene Seite mit seinen Kursen und Lizenzen.
- Ein Kurs findet an festen Daten statt, in den Räumen der VIAK in Zürich oder online. Gebucht wird online über den Warenkorb.
- Die Rechnung für einen Kurs kommt erst, wenn die Mindestanzahl Teilnehmende erreicht und der Kurs bestätigt ist. Bezahlt wird mit dem QR-Einzahlungsschein oder per Kreditkarte.
- Kurspreise enthalten keine Mehrwertsteuer: Kurse sind von der MWST ausgenommen. Lizenzpreise verstehen sich exkl. {{ $vat }}% MWST.
- Wer keinen passenden Laptop hat, kann zum Kurs einen Computer mieten, für CHF {{ $rental }} (exkl. MWST).
- Lizenzen werden online bestellt und per Kreditkarte oder per Rechnung bezahlt. Die VIAK sendet sie nach Zahlungseingang per E-Mail.
- Firmenschulungen und Individualschulungen gibt es auf Anfrage, für Teams oder Einzelpersonen, beim Kunden oder in Zürich, zugeschnitten auf das eigene Projekt und die eigene Software.
- Wer nicht weiss, welcher Kurs passt, kann anrufen: eine kurze Beratung, kostenlos und unverbindlich.
- Kontakt: Visualisierungs-Akademie Schweiz GmbH, Limmatstrasse 291, 8005 Zürich, Telefon +41 43 501 40 40, hallo@visualisierungs-akademie.ch.

## Seiten

- [Alle Angebote]({{ $url(\App\Support\SiteUrl::offers()) }}): Alles, was die VIAK anbietet, nach Bereichen.
- [Kurse]({{ $url(\App\Support\SiteUrl::courses()) }}): Alle Kurse mit den nächsten Daten und Preisen.
- [Software]({{ $url(\App\Support\SiteUrl::softwareIndex()) }}): Alle Lizenzen mit Preisen.
- [Firmenschulung]({{ $url(\App\Support\SiteUrl::training()) }}): Individual- und Firmenschulungen anfragen.
- [Über uns]({{ $url(\App\Support\SiteUrl::about()) }}): Geschichte, Experten und Team.
- [Kontakt]({{ $url(\App\Support\SiteUrl::contact()) }}): Adresse, Anreise, Impressum und Datenschutz.
@foreach ($sections as $section)

## {!! $section['title'] !!}

@foreach ($section['tiles'] as $tile)
- [{!! $tile['title'] !!}]({{ $url($tile['href']) }}): {!! $tile['kind'] !!}
@endforeach
@endforeach

## Optional

- [Allgemeine Geschäftsbedingungen]({{ $url('/media/downloads/Visualisierungs-Akademie_AGB_Jul24.pdf') }})
