{{--
	A message from the Kontakt form, to the office ([[ContactMessage]]). New:
	legacy's Kontakt had no form. Answer it with *Antworten*: Reply-To is the
	sender.
--}}
@component('mail::message')
<p>Über das Kontaktformular ist eine Nachricht eingegangen.</p>
<table class="content-table" cellpadding="0" cellspacing="0">
  <tr>
    <td>Name</td>
    <td>{{ $name }}</td>
  </tr>
  <tr>
    <td>E-Mail</td>
    <td><a href="mailto:{{ $email }}">{{ $email }}</a></td>
  </tr>
</table>
<p>{!! nl2br(e($text)) !!}</p>
@endcomponent
