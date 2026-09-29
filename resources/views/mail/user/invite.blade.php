{{--
	*Dein VIAK-Zugang*: an account an admin created, and the link to set its
	password ([[AccountInvite]]).
	../viak.ch/resources/views/mail/user/expert/create.blade.php, its text as it
	is. Legacy sent it to experts only (a student's password was typed by the
	admin); the rework invites both (#26).
--}}
@component('mail::message')
<h1>Zugangsdaten</h1>
<p>Guten Tag {{ $user->first_name }} {{ $user->last_name }}</p>
<p>Es wurde ein Konto für Dich eingerichtet. Um die Erstellung Deines Kontos abzuschliessen, müssen wir Deine E-Mail-Adresse verifizieren. Bitte klick auf folgenden Button:</p>
<p class="py-2x"><a href="{{ $confirmUrl }}" class="button button-primary">E-Mail Bestätigen</a></p>
<p><small>Sollte der Button "E-Mail Bestätigen" nicht funktionieren, kopier bitte die untenstehende URL und fügen sie in die Adresszeile Deines Browses ein:<br><span class="break-all">{{ $confirmUrl }}</span></small></p>
@include('mail.partials.signature')
@endcomponent
