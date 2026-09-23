@php
	$locale = app()->getLocale();
	$event = $booking->event;
	$user = $booking->user;
	$name = trim("{$user->first_name} {$user->last_name}");

	$dates = $event->dates
		->map(fn ($date) => $date->date->format('d.m.Y'))
		->implode(', ');

	$experts = $event->experts
		->map(fn ($expert) => trim("{$expert->first_name} {$expert->last_name}"))
		->implode(', ');

	/*
		**The date the course finished, not today.** Legacy dates the line
		`Zürich, {closed_at}` and then builds the *filename* from `date('d-m-Y',
		time())`, so a confirmation reprinted a year later is filed under a date
		that appears nowhere on it. One date, read from the event, used for both
		([[RenderParticipationConfirmation]]).
	*/
	$closed = $event->closed_at ?? $event->date;
@endphp

<x-documents.layout title="Teilnahmebestätigung">
	{{--
		*Teilnahmebestätigung* — `pdf/course/participant-confirmation.blade.php`
		([[08-accounts]]).

		The certificate a student gets when a course is closed. Legacy generates
		it **inside `EventClosedStudent`'s constructor**, so the document exists
		only if the mail was composed, and is made again every time that mail is
		retried ([[RenderParticipationConfirmation]]).
	--}}
	<div class="addresses">
		{{-- The label is the address's first line, as in legacy — one `<br>`,
		     not the invoice's spaced label. --}}
		<div class="address">
			Kursteilnehmer:in<br>
			@foreach ($user->addressLines() as $line)
				{{ $line }}@if (! $loop->last)<br>@endif
			@endforeach
		</div>
	</div>

	<h1 class="title">Teilnahmebestätigung<br>{{ $name }}</h1>

	<div class="date">{{ config('documents.place') }}, {{ $closed->format('d.m.Y') }}</div>

	{{-- **Both columns are stated in millimetres, and they have to be.** dompdf
	     resolves a `width: 100%` table against the sheet's padding box rather
	     than its content box, so an `auto` column stretches past the 168mm
	     column and the rules run off the right edge of the page. Every table in
	     these documents sums its columns to 168 explicitly. --}}
	<table class="items">
		<tbody>
			<tr>
				<td style="width: 36mm">Kurs</td>
				<td style="width: 132mm"><strong>{{ $event->course->getTranslation('title', $locale) }}</strong></td>
			</tr>
			<tr>
				<td>Datum</td>
				<td><strong>{{ $dates }}</strong></td>
			</tr>
			@if ($experts !== '')
				<tr>
					<td>Experte</td>
					<td><strong>{{ $experts }}</strong></td>
				</tr>
			@endif
			<tr>
				<td>Buchung</td>
				<td><strong>{{ $booking->number }}</strong></td>
			</tr>
		</tbody>
	</table>

	<p>Wir bestätigen, dass {{ $name }} den oben aufgeführten Kurs erfolgreich absolviert hat.</p>

	{{--
		The course's own summary, where it has one — what the student actually
		covered, which is the part of this document that makes it worth keeping.

		**Legacy guards it against the string `'null'`** (`$course->summary !=
		'null'`), a value its editor wrote into the column. The port does not
		carry those across, so the guard has nothing left to catch and is not
		reproduced ([[PortCourses]]).
	--}}
	@if (filled($event->course->getTranslation('summary', $locale)))
		<x-site.rich-text :html="$event->course->getTranslation('summary', $locale)" />
	@endif

	{{-- `pdf/partials/signature.blade.php`: a name and the company, no image.
	     Read wrongly as empty once; it is on all 447 confirmations in the prod
	     storage copy ([[Open-Questions]] 21). --}}
	<p>{{ config('documents.signature') }}<br>{{ config('documents.creditor.name') }}</p>
</x-documents.layout>
