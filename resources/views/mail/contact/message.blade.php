{{--
	A message from the Kontakt form, to the office ([[ContactMessage]]). New:
	legacy's Kontakt had no form. Answer it with *Antworten*: Reply-To is the
	sender.

	The table ends the mail, so it drops `.content-table`'s bottom rule: the
	footer's rule follows straight after, and the two read as a double line.
--}}
@component('mail::message')
<p>Über das Kontaktformular ist eine Nachricht eingegangen.</p>
<table class="content-table" cellpadding="0" cellspacing="0" style="border-bottom: none;">
  <tr>
    <td>Name</td>
    <td>{{ $name }}</td>
  </tr>
  <tr>
    <td>E-Mail</td>
    <td><a href="mailto:{{ $email }}">{{ $email }}</a></td>
  </tr>
  <tr>
    <td>Nachricht</td>
    <td style="vertical-align: top;">{!! nl2br(e($text)) !!}</td>
  </tr>
</table>
@endcomponent
