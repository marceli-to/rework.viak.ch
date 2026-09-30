{{--
	*Teilnahmebestätigung* to a student who attended ([[EventClosedCustomer]]).
	../viak.ch/resources/views/mail/event/attendance.blade.php, its text as it is.
--}}
@component('mail::message')
<h1>Teilnahmebestätigung – {{ $course }}</h1>
<p>Guten Tag {{ $booking->user->name }}</p>
<p>Hiermit bestätigen wir Deine Teilnahme an unserem Kurs:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td>Kurs</td>
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
</table>
<p>Möchtest Du weitere Schulungen besuchen? Verwalte Deine Kurse und Deine persönlichen Daten bequem und einfach unter: <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>visualisierungs-akademie.ch/profil</strong></a></p>
@include('mail.partials.signature')
@endcomponent
