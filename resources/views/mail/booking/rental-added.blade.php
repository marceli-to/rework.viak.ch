{{--
	*Buchung Mietcomputer* to the student, a laptop added to a seat ([[RentalAdded]]).
	../viak.ch/resources/views/mail/booking/rental-added.blade.php, its text as it
	is. Only legacy's second branch is reachable: a laptop can be added only
	before the seat is invoiced ([[SetRental]]), so there is never a rental
	invoice to attach yet; it comes on the course's invoice.
--}}
@component('mail::message')
<h1>Buchung Mietcomputer – {{ $course }}</h1>
<p>Guten Tag {{ $booking->user->name }}</p>
<p>Gerne bestätigen wir die Buchung des Mietcomputers wie folgt:</p>
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
  <tr>
    <td>Kosten</td>
    <td>CHF {{ $booking->rental_fee }} (exkl. {{ config('invoice.vat_label') }})</td>
  </tr>
</table>
<p>Die Rechnung erhältst Du, sobald wir wissen, dass die Mindestanzahl Teilnehmende erreicht ist und der Kurs definitiv stattfinden wird.</p>
<p>Um diese Buchung zu annullieren, klicke bitte <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none;"><strong>hier</strong></a>.</p>
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
@include('mail.partials.signature')
@endcomponent
