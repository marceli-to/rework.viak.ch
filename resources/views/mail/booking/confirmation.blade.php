{{--
	*Buchungsbestätigung* to the student ([[BookingCompleted]]).
	../viak.ch/resources/views/mail/booking/confirmation.blade.php, its text as it is.
--}}
@component('mail::message')
<h1>Buchungsbestätigung – {{ $course }}</h1>
<p>Guten Tag {{ $user->name }}</p>
@if ($event->free_of_charge)
<p>Vielen Dank für Deine Anmeldung für den Event «{{ $course }}». Gerne bestätigen wir Deine Anmeldung wie folgt:</p>
@else
<p>Vielen Dank für Deine Anmeldung für den Kurs «{{ $course }}». Gerne bestätigen wir Deine Anmeldung wie folgt:</p>
@endif
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
  @if ($booking->has_rental)
    <tr>
      <td>Mietcomputer</td>
      <td>gebucht</td>
    </tr>
  @endif
</table>
@if ($event->free_of_charge)
<p>Die definitive Einladung für den Event erhältst Du, sobald wir wissen, dass die Mindestanzahl Teilnehmende erreicht ist und der Event definitiv stattfinden wird.</p>
@else
<p>Die Rechnung sowie die definitive Einladung für den Kurs erhältst Du, sobald wir wissen, dass die Mindestanzahl Teilnehmende erreicht ist und der Kurs definitiv stattfinden wird.</p>
@endif
<p>Um diese Buchung zu annullieren, klicke bitte <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>hier</strong></a>.</p>
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
@include('mail.partials.signature')
<p class="small">Möchtest Du weitere Schulungen besuchen? Verwalte Deine Kurse und Deine persönlichen Daten bequem und einfach unter: <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>visualisierungs-akademie.ch/profil</strong></a></p>
@endcomponent
