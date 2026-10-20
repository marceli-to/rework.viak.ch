{{--
	*Zahlungsbestätigung Rechnung* to the customer and to the office
	([[InvoicePaidConfirmation]], [[InvoicePaidNotification]]).
	../viak.ch/resources/views/mail/payment/notification.blade.php, its text as
	it is, but for the office's *Teilnehmer*: a licence order has a customer,
	not a participant, so it is *Kund:in* as in the other office mails.
--}}
@component('mail::message')
@if ($recipient === 'customer')
<h1>Zahlungsbestätigung Rechnung {{ $invoice->number }}</h1>
<p>Guten Tag {{ $invoice->user->name }}</p>
<p>Wir haben deine Zahlung erhalten, welche wir gerne wie folgt bestätigen:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Rechnung</td>
    <td>{{ $invoice->number }}</td>
  </tr>
  <tr>
    <td>Betrag</td>
    <td>CHF {{ $invoice->grand_total }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $paidOn }}</td>
  </tr>
  <tr>
    <td>Bezahlt per</td>
    <td>Kreditkarte</td>
  </tr>
</table>
<p>Möchtest Du weitere Schulungen besuchen? Verwalte Deine Kurse und Deine persönlichen Daten bequem und einfach unter: <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>visualisierungs-akademie.ch/konto</strong></a></p>
@else
<h1>Zahlungseingang</h1>
<p>Folgende Rechnung wurde per Kreditkarte bezahlt:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Rechnung</td>
    <td>{{ $invoice->number }}</td>
  </tr>
  <tr>
    <td>Betrag</td>
    <td>CHF {{ $invoice->grand_total }}</td>
  </tr>
  <tr>
    <td>Datum</td>
    <td>{{ $paidOn }}</td>
  </tr>
  <tr>
    <td>Kund:in</td>
    <td>{{ $invoice->user->name }}@if ($invoice->user->city), {{ $invoice->user->city }}@endif</td>
  </tr>
</table>
@endif
@include('mail.partials.signature')
@endcomponent
