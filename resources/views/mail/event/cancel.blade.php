{{--
	*Kursabsage* to each student who held a seat, and to each expert
	([[EventCancelCustomer]], [[EventCancelExpert]]).
	../viak.ch/resources/views/mail/event/cancel.blade.php, its text as it is;
	the code for an invoice already paid is issued by [[CancelBooking]].
--}}
@component('mail::message')
<h1>Kursabsage – {{ $course }}</h1>
@if ($recipient === 'customer')
<p>Guten Tag {{ $user->name }}</p>
<p>Leider müssen wir den folgenden Kurs absagen:</p>
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
    <td>Experten</td>
    <td>{{ $experts }}</td>
  </tr>
</table>
<p>Deine Buchung für diesen Kurs wurde automatisch annulliert.</p>
@if ($credit)
<p>Da die Rechnung bereits bezahlt ist, haben wir Dir einen Rabatt-Code für den bezahlten Betrag ausgestellt. Dieser kann bei der nächsten Buchung angewendet werden und lautet: <nobr><strong>{{ $credit->code }}</strong></nobr>. Falls du lieber eine Rückerstattung des Betrages möchtest, dann nimm bitte mit uns Kontakt auf.</p>
@endif
@if (count($nextDates))
<p>Es würde uns natürlich freuen, wenn Du Dich für die nächste Durchführung dieses Kurses erneut anmelden würdest. Hier die nächsten Daten:</p>
<ul>
@foreach ($nextDates as $next)
  <li>
    <a href="{{ $coursePage }}" target="_blank" style="color: #000000; text-decoration: none;">
      <strong>{{ $next }}</strong>
    </a>
  </li>
@endforeach
</ul>
@else
<p>Falls der Kurs an einem neuen Datum stattfinden wird, so findest Du dieses in Kürze auf unserer Website.</p>
@endif
<p>Bei Fragen stehen wir Dir natürlich gerne zur Verfügung.</p>
<p>Falls Du eine andere Schulung besuchen möchtest: All unsere Kurse und Angebote findest Du <a href="{{ $courses }}" target="_blank" style="color: #000000; text-decoration: none; font-weight:bold"><strong>hier</strong></a>.</p>
@endif
@if ($recipient === 'expert')
<p>Leider müssen wir den folgenden Kurs absagen:</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td width="120">Kurs</td>
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
</table>
@endif
@include('mail.partials.signature')
@endcomponent
