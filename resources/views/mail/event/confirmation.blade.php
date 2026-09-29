{{--
	*Kursbestätigung* to a student, *Bestätigung* to an expert
	([[EventConfirmationStudent]], [[EventConfirmationExpert]]).
	../viak.ch/resources/views/mail/event/confirmation.blade.php, its text as it is,
	with one change: legacy sent the rental on an invoice of its own, with its
	own paragraph and button; the rework bills it as a line of the one invoice
	([[RaiseInvoiceForBooking]]), so one paragraph and one button cover both.

	*Zahlung per Kreditkarte* goes to a placeholder for now: legacy's Stripe
	page is not rebuilt yet (`Todo.md`, `Open-Questions.md` #28).
--}}
@component('mail::message')
<h1>Kursbestätigung – {{ $course }}</h1>
@if ($recipient === 'student')
<p>Guten Tag {{ $user->name }}</p>
<p>Hiermit bestätigen wir die Durchführung des oben erwähnten {{ $event->free_of_charge ? 'Events' : 'Kurses' }}:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Buchung</td>
    <td>{{ $booking->number }}</td>
  </tr>
  <tr>
    <td>{{ $event->free_of_charge ? 'Event' : 'Kurs' }}</td>
    <td>{{ $course }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $dates }}</td>
  </tr>
  <tr>
    <td>Experten</td>
    <td>{{ $experts }}</td>
  </tr>
  <tr>
    <td>Ort</td>
    <td>{{ $place }}</td>
  </tr>
  <tr>
    <td>Kosten</td>
    <td>{{ $event->free_of_charge ? 'kostenlos' : 'CHF '.$fee }}</td>
  </tr>
</table>
@if ($invoice)
<p>Die Rechnung für die Kurskosten findest Du im Anhang.<br>Falls Du die Rechnung lieber mit Kreditkarte bezahlen möchtest, dann klicke bitte auf den nachfolgenden Link.</p>
<p class="py-2x"><a href="{{ $payment }}" target="_blank" class="button button-primary" style="text-decoration: none;"><strong>Zahlung per Kreditkarte</strong></a></p>
@endif
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
<p>Möchtest Du weitere Schulungen besuchen? Verwalte Deine Kurse und Deine persönlichen Daten bequem und einfach unter: <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>visualisierungs-akademie.ch/profil</strong></a></p>
@endif
@if ($recipient === 'expert')
<p>Sali {{ $user->first_name }}</p>
<p>Hiermit bestätigen wir die Durchführung des oben erwähnten Kurses:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Kurs</td>
    <td>{{ $course }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $dates }}</td>
  </tr>
  <tr>
    <td>Experten</td>
    <td>{{ $experts }}</td>
  </tr>
</table>
<p>Weitere Informationen zu diesem Kurs findest Du  <a href="{{ $expertPage }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>hier</strong></a>.</p>
<p>Wende Dich bei Fragen doch bitte umgehend an uns.</p>
@endif
@include('mail.partials.signature')
@endcomponent
