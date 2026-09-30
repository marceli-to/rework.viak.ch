{{--
	*Neue Anmeldung* to each expert and to the office ([[BookingCreatedInfo]]).
	../viak.ch/resources/views/mail/booking/created-info.blade.php, its text as it is.
--}}
@component('mail::message')
<p>Hallo {{ $greeting }}</p>
<p>Für den Kurs «{{ $course }}» ist eine neue Anmeldung eingegangen.</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td>Kund:in</td>
    <td>{{ $booking->user->name }}</td>
  </tr>
  <tr>
    <td>Kurs</td>
    <td>{{ $course }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $dates }}</td>
  </tr>
  <tr>
    <td>Ort</td>
    <td>{{ $place }}</td>
  </tr>
</table>
@if ($expertPage)
<p>Alle weitere Details findest du <a href="{{ $expertPage }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>hier</strong></a>.</p>
@endif
@include('mail.partials.signature')
@endcomponent
