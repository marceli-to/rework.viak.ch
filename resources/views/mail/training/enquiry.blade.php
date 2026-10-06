{{--
	A Firmenschulung enquiry, to the office ([[TrainingEnquiry]]), as Kontakt's
	message is drawn (`mail/contact/message`). Answer it with *Antworten*:
	Reply-To is the contact person.
--}}
@component('mail::message')
<p>Über die Seite Firmenschulung ist eine Anfrage eingegangen.</p>
<table class="content-table" cellpadding="0" cellspacing="0" style="border-bottom: none;">
  <tr>
    <td>Firma</td>
    <td>{{ $company }}</td>
  </tr>
  <tr>
    <td>Ansprechperson</td>
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
