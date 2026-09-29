{{--
	*Annullationsbestätigung* with the penalty ([[BookingCancelledWithPenalty]]).
	../viak.ch/resources/views/mail/booking/cancellation-with-penalty.blade.php,
	its text as it is: paid in full with money left over, a code for it
	([[CancelBooking]]); paid, nothing more to do; unpaid, the invoice attached.
--}}
@component('mail::message')
<h1>Annullationsbestätigung – {{ $course }}</h1>
<p>Guten Tag {{ $booking->user->name }}</p>
<p>Wir haben Deine Annullation für den Kurs «{{ $course }}» erhalten.</p>
<p>Die kurzfristige Annullation hat gemäss unseren AGB Kosten zur Folge. Diese belaufen sich auf CHF {{ $cost }}.– ({{ $percent }}% der Kurskosten).</p>
@if ($paid && $credit)
<p>Da die gesamte Rechnung bereits bezahlt ist, haben wir Dir einen Rabatt-Code für den zuviel bezahlten Betrag ausgestellt. Dieser kann bei der nächsten Buchung angewendet werden und lautet: <nobr><strong>{{ $credit->code }}</strong></nobr>. Falls du lieber eine Rückerstattung des zuviel bezahlten Betrages möchtest, dann nimm bitte mit uns Kontakt auf.</p>
@elseif ($paid)
<p>Da die Rechnung bereits bezahlt ist, musst Du nichts weiter unternehmen.</p>
@else
<p>Die entsprechende Rechnung liegt diesem Mail bei.</p>
@endif
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Buchung</td>
    <td>{{ $booking->number }}</td>
  </tr>
  <tr>
    <td>Kurs</td>
    <td>{{ $course }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $dates }}</td>
  </tr>
</table>
<p>Möchtest Du eine andere Schulung besuchen? Unser Schulungsangebot findest Du unter: <a href="{{ $courses }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>visualisierungs-akademie.ch/kurse</strong></a></p>
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
@include('mail.partials.signature')
@endcomponent
