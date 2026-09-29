{{--
	*Buchung Mietcomputer* to the office ([[RentalAddedInfoAdmin]]).
	../viak.ch/resources/views/mail/booking/rental-added-info.blade.php, its text as it is.
--}}
@component('mail::message')
<p>Hallo</p>
<p>Für den Kurs «{{ $course }}» wurde ein Mietcomputer gebucht</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td>Student:in</td>
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
</table>
@include('mail.partials.signature')
@endcomponent
