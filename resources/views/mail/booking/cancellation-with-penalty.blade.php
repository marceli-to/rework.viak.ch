{{--
	*Annullationsbestätigung* with the penalty ([[BookingCancelledWithPenalty]]).
	../viak.ch/resources/views/mail/booking/cancellation-with-penalty.blade.php,
	its text as it is, without the discount-code branch (see
	`cancellation.blade.php`): paid means nothing more to do, unpaid means the
	invoice is attached.
--}}
@component('mail::message')
<h1>Annullationsbestätigung – {{ $course }}</h1>
<p>Guten Tag {{ $booking->user->name }}</p>
<p>Wir haben Deine Annullation für den Kurs «{{ $course }}» erhalten.</p>
<p>Die kurzfristige Annullation hat gemäss unseren AGB Kosten zur Folge. Diese belaufen sich auf CHF {{ $cost }}.– ({{ $percent }}% der Kurskosten).</p>
@if ($paid)
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
