{{--
	*Reminder* to the office: a date ten days out, neither confirmed nor
	cancelled ([[EventCancelOrConfirmReminder]]).
	../viak.ch/resources/views/mail/event/cancel-or-confirm-reminder.blade.php, its
	text as it is; the button opens the rework's course-date form.
--}}
@component('mail::message')
<h1>Reminder – {{ $course }}</h1>
<p>Der folgende Kurs wurde noch nicht bestätigt oder abgesagt:</p>
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
<p class="py-2x">
  <a href="{{ $edit }}" class="button button-primary">Kurs bearbeiten</a>
</p>
@include('mail.partials.signature')
@endcomponent
