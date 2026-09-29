{{--
	A course message, to a booked student ([[EventMessageStudent]]).
	../viak.ch/resources/views/mail/event/message-student.blade.php, its text as it is.
	Attachments link to the gated download, not legacy's public
	`/storage/uploads/` path: a message's file is as private as the message
	([[MediaPolicy]]).
--}}
@component('mail::message')
<p><small><em>{{ $post->author->name }} ({{ $post->author->email }}) hat folgende Nachricht gesendet:</em></small></p>
{!! $body !!}
@if ($post->media->isNotEmpty())
  <div>Anhänge</div>
  @foreach ($post->media as $file)
    <div>
      <a href="{{ route('media.download', $file->uuid) }}" target="_blank">
        – {{ $file->caption ? $file->caption.' ('.$file->original_name.')' : $file->original_name }}
      </a>
    </div>
  @endforeach
@endif
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
    <td>Ort</td>
    <td>{{ $event->online ? 'Onlinekurs' : $place }}</td>
  </tr>
</table>
@include('mail.partials.signature')
@endcomponent
