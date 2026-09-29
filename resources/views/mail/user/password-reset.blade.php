{{--
	*Passwort zurücksetzen* ([[PasswordReset]]). New: legacy sent Laravel's stock
	notification, in English (Marcel, 2026-09-29: translate it). Worded as the
	verification mail is, in the same *Du*, on the same theme.
--}}
@component('mail::message')
<h1>Passwort zurücksetzen</h1>
<p>Guten Tag {{ $user->first_name }} {{ $user->last_name }}</p>
<p>Wir haben eine Anfrage erhalten, das Passwort für Dein Konto zurückzusetzen. Klicke dafür bitte auf folgenden Button:</p>
<p class="py-2x"><a href="{{ $resetUrl }}" class="button button-primary">Passwort zurücksetzen</a></p>
<p>Dieser Link ist {{ $minutes }} Minuten gültig.</p>
<p>Falls Du kein neues Passwort angefordert hast, musst Du nichts weiter unternehmen.</p>
@include('mail.partials.signature')
<p><small>Sollte der Button "Passwort zurücksetzen" nicht funktionieren, kopiere bitte die untenstehende URL und füge sie in die Adresszeile Deines Browsers ein:<br><span class="break-all">{{ $resetUrl }}</span></small></p>
@endcomponent
