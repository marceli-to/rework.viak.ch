{{--
	*Bestätigung Anmeldung*: confirm the address ([[EmailVerification]]).
	../viak.ch/resources/views/mail/user/student/register.blade.php, its text as
	it is, typos and all (*der der*, *fügen*, *Browses*), in place of Laravel's
	stock verification mail. Also what a changed address gets ([[UpdateProfile]]).
--}}
@component('mail::message')
<h1>Bestätigung Account</h1>
<p>Guten Tag {{ $user->first_name }} {{ $user->last_name }}</p>
<p>Vielen Dank für Dein Interesse an der der Visualisierungs-Akademie Schweiz GmbH.</p>
<p>Um die Registration Deines Accounts abzuschliessen, musst Du noch Deine E-Mail-Adresse bestätigen. Klicke dafür bitte auf folgenden Button:</p>
<p class="py-2x"><a href="{{ $verifyUrl }}" class="button button-primary">E-Mail Bestätigen</a></p>
<p>Sobald Du das erledigt hast, kannst Du bei uns Deinen ersten Kurs buchen.</p>
@include('mail.partials.signature')
<p><small>Sollte der Button "E-Mail Bestätigen" nicht funktionieren, kopier bitte die untenstehende URL und fügen sie in die Adresszeile Deines Browses ein:<br><span class="break-all">{{ $verifyUrl }}</span></small></p>
@endcomponent
