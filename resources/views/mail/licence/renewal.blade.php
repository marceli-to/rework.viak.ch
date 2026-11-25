{{--
	A rental's renewal reminder, sent by hand from the dashboard
	([[RentalRenewalReminder]], #83). New, so no legacy text to keep: in the
	order mail's words and table (`mail/licence/order`).
--}}
@component('mail::message')
<h1>Deine Jahresmietlizenz läuft bald ab</h1>
<p>Guten Tag {{ $user->name }}</p>
<p>Deine Jahresmietlizenz läuft am {{ $ends }} ab:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Software</td>
    <td>{{ $line }}</td>
  </tr>
  <tr>
    <td>Bestellung</td>
    <td>{{ $number }} vom {{ $ordered }}</td>
  </tr>
  <tr>
    <td>Gültig bis</td>
    <td>{{ $ends }}</td>
  </tr>
</table>
<p>Um sie für ein weiteres Jahr zu verlängern, bestellst Du sie einfach wieder bei uns.</p>
<p class="py-2x"><a href="{{ $renew }}" target="_blank" class="button button-primary" style="text-decoration: none;"><strong>Lizenz verlängern</strong></a></p>
<p>Bei Fragen stehen wir Dir gerne zur Verfügung.</p>
@include('mail.partials.signature')
@endcomponent
