{{--
	*Annullationsbestätigung*, no penalty ([[BookingCancelledCustomer]]).
	../viak.ch/resources/views/mail/booking/cancellation.blade.php, its text as it
	is, **without the discount-code paragraph**: legacy's mail issued a code for
	an invoice already paid while it rendered. Refunds are VIAK's to handle by
	hand (Marcel, 2026-09-17, [[CancelBooking]]), so no code is promised.
--}}
@component('mail::message')
<h1>Annullationsbestätigung – {{ $course }}</h1>
<p>Guten Tag {{ $booking->user->name }}</p>
<p>Wir haben Deine Annullation für den Kurs «{{ $course }}» erhalten.</p>
@if ($credit)
<p>Da die Rechnung bereits bezahlt ist, haben wir Dir einen Rabatt-Code für den bezahlten Betrag ausgestellt. Dieser kann bei der nächsten Buchung angewendet werden und lautet: <nobr><strong>{{ $credit->code }}</strong></nobr>. Falls du lieber eine Rückerstattung des Betrages möchtest, dann nimm bitte mit uns Kontakt auf.</p>
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
<p>Möchtest Du stattdessen einen anderen Kurs besuchen? Unser Schulungsangebot findest Du unter: <a href="{{ $courses }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>visualisierungs-akademie.ch/kurse</strong></a></p>
@include('mail.partials.signature')
@endcomponent
