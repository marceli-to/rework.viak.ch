@php
	$locale = app()->getLocale();
	$title = $event->course->getTranslation('title', $locale);

	$experts = $event->experts
		->map(fn ($expert) => trim("{$expert->first_name} {$expert->last_name}"))
		->implode(', ');
@endphp

<x-documents.layout :title="'Teilnehmer:innen-Liste '.$title">
	{{--
		*Teilnehmer:innen-Liste* — `pdf/course/participants-list.blade.php`
		([[08-accounts]]).

		The list an expert takes into the room: who is coming, and how to reach
		them. **It is the only place a participant's phone number and email
		address appear**, which is what made legacy's missing ownership check
		matter — `GET /pdf/teilnehmer-liste/{event}` is gated by
		`role:admin,expert` and nothing else, so any of the 18 accounts holding
		the Expert role could download the contact details of every student on
		every course in the archive (finding 5).

		Here [[EventPolicy::viewParticipants]] answers first, and it is the same
		rule the expert portal's course screen already asks
		([[ExpertPortalController]]).

		**Not filed as a `UserDocument`.** This belongs to nobody in particular —
		it is not a customer's own record the way an invoice or a certificate is
		— so it is rendered on request and handed over, never written to disk.
		Legacy writes each one into the public directory and forgets it: 294
		loose PDFs of names and contact details, referenced by no row and deleted
		by nothing ([[Todo]]).
	--}}
	<div class="addresses">
		<div class="address">
			<table>
				<tr>
					<td style="width: 25mm">Kurs</td>
					<td>{{ $title }}</td>
				</tr>
				@if ($experts !== '')
					<tr>
						<td>Experte</td>
						<td>{{ $experts }}</td>
					</tr>
				@endif
				<tr>
					<td>Kurs-Nr.</td>
					<td>{{ $event->number() }}</td>
				</tr>
			</table>
		</div>
	</div>

	<h1 class="title">Teilnehmer:innen-Liste<br>{{ $title }}</h1>

	<div class="date">{{ config('documents.place') }}, {{ now()->format('d.m.Y') }}</div>

	<table class="items">
		<thead>
			<tr>
				<th style="width: 84mm">Teilnehmer</th>
				<th style="width: 34mm">Telefon</th>
				<th style="width: 50mm">E-Mail</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($bookings as $booking)
				<tr>
					<td><strong>{{ trim("{$booking->user->first_name} {$booking->user->last_name}") }}@if ($booking->user->city), {{ $booking->user->city }}@endif</strong></td>
					<td>{{ $booking->user->phone ?: '–' }}</td>
					<td>{{ $booking->user->email ?: '–' }}</td>
				</tr>
			@empty
				<tr>
					<td colspan="3">Es sind keine Anmeldungen für diesen Kurs vorhanden.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</x-documents.layout>
