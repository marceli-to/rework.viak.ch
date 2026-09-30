{{--
	*Stornierung Mietcomputer* to the office ([[RentalCancelledInfoAdmin]]).
	../viak.ch/resources/views/mail/booking/rental-cancellation-info.blade.php, its text as it is.
--}}
@component('mail::message')
<p>Hallo</p>
<p>Ein Mietcomputer für  «{{ $course }}» wurde storniert</p>
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
</table>
@include('mail.partials.signature')
@endcomponent
