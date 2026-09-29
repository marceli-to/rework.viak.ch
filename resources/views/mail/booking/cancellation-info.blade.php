{{--
	*Abmeldung für …* to the office ([[BookingCancelledInfoAdmin]]).
	../viak.ch/resources/views/mail/booking/cancellation-info.blade.php, its text as it is.
--}}
@component('mail::message')
<p>Hallo {{ $greeting }}</p>
<p>Vom Kurs «{{ $course }}» hat sich jemand abgemeldet.</p>
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
    <td>Ort</td>
    <td>{{ $place }}</td>
  </tr>
</table>
@include('mail.partials.signature')
@endcomponent
