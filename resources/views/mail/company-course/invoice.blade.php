{{--
	A Firmenkurs's invoice ([[CompanyCourseInvoice]]), in the course mails'
	table (`mail/event/confirmation`), with *Sie*.
--}}
@component('mail::message')
<h1>Rechnung {{ $invoice->number }}</h1>
<p>Guten Tag</p>
<p>Vielen Dank für Ihren Auftrag. Im Anhang finden Sie die Rechnung für folgende Firmenschulung:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Schulung</td>
    <td>{{ $course->name() }}</td>
  </tr>
@if ($dates)
  <tr>
    <td>Datum</td>
    <td>{{ $dates }}</td>
  </tr>
@endif
  <tr>
    <td>Betrag</td>
    <td>CHF {{ $invoice->grand_total }}</td>
  </tr>
  <tr>
    <td>Zahlbar bis</td>
    <td>{{ $invoice->due_at?->format('d.m.Y') }}</td>
  </tr>
</table>
<p>Bitte bezahlen Sie die Rechnung mit dem QR-Einzahlungsschein auf der letzten Seite.</p>
<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
@include('mail.partials.signature')
@endcomponent
