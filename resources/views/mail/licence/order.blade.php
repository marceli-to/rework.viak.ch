{{--
	A shop order, to the customer and to the office ([[LicenceOrderConfirmation]],
	[[LicenceOrderInfoAdmin]]). New, so no legacy text to keep: written in the
	course mails' words and table (`mail/event/confirmation`).
--}}
@component('mail::message')
@if ($recipient === 'customer')
<h1>Bestellbestätigung – Bestellung {{ $order->number }}</h1>
<p>Guten Tag {{ $user->name }}</p>
<p>Vielen Dank für Deine Bestellung, welche wir gerne wie folgt bestätigen:</p>
@else
<h1>Neue Softwarebestellung {{ $order->number }}</h1>
<p>Im Shop wurde folgende Software bestellt:</p>
@endif
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Bestellung</td>
    <td>{{ $order->number }}</td>
  </tr>
@if ($recipient === 'office')
  <tr>
    <td>Kund:in</td>
    <td>{{ $user->name }}, {{ $user->email }}</td>
  </tr>
@endif
  <tr>
    <td>Software</td>
    <td>@foreach ($lines as $line){{ $line->description() }}@if ($recipient === 'office' && $line->sku) ({{ $line->sku }})@endif @if (! $loop->last)<br>@endif @endforeach</td>
  </tr>
  <tr>
    <td>Betrag</td>
    <td>{{ $amount }}</td>
  </tr>
  <tr>
    <td>Lizenzen an</td>
    <td>{{ $order->deliveryEmail() }}</td>
  </tr>
@if ($invoice)
  <tr>
    <td>Zahlung</td>
    <td>{{ $paid ? 'bezahlt per Kreditkarte' : 'offen' }}</td>
  </tr>
@endif
</table>
@if ($recipient === 'customer')
@if (! $invoice)
<p>Wir senden Dir die Lizenzen in Kürze an {{ $order->deliveryEmail() }}.</p>
@elseif ($paid)
<p>Die Rechnung liegt bei, sie ist bereits bezahlt. Wir senden Dir die Lizenzen in Kürze an {{ $order->deliveryEmail() }}.</p>
@else
<p>Die Rechnung liegt bei. Du kannst sie mit dem QR-Einzahlungsschein oder per Kreditkarte bezahlen. Die Lizenzen senden wir Dir nach Zahlungseingang.</p>
<p class="py-2x"><a href="{{ $payment }}" target="_blank" class="button button-primary" style="text-decoration: none;"><strong>Zahlung per Kreditkarte</strong></a></p>
@endif
<p>Deine Bestellungen findest Du jederzeit in Deinem Profil: <a href="{{ $portal }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>visualisierungs-akademie.ch/konto</strong></a></p>
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
@else
<p><a href="{{ $dashboard }}" target="_blank" style="color: #000000; font-weight:bold">Bestellung im Dashboard öffnen</a></p>
@endif
@include('mail.partials.signature')
@endcomponent
