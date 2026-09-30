@php
	$locale = app()->getLocale();
	$title = $event->course->getTranslation('title', $locale);
	// The dashboard's heading for the same event: the course number before the
	// name ([[Event/Show]]). The browser tab keeps the bare name.
	$heading = $event->course->number.' '.$title;
@endphp

<x-layout.site :title="$title" :heading="$heading" auth>
	{{--
		One booked seat — `backend/student/views/Course.vue`.

		Three blocks under the heading: the booking itself, the notes the expert
		or VIAK posted to the course, and the course materials.

		**The heading is the dashboard's**: teal, with the course number
		(Marcel, 2026-09-30). Legacy's
		`article.content-text--event` drew it black and without the number.
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">{{ $heading }}</h1>
			<x-ui.back-link :href="\App\Support\SiteUrl::studentPortal()" />
		</x-slot:aside>
	</x-layout.article>

	<div class="mt-48 lg:mt-64">
		<x-ui.collapsible dashboard title="Buchung" :expanded="true">
			<x-row.event :event="$event" :booking="$booking">
				{{-- The seat's own badges beside the course's state, as the
				     dashboard's student page draws them: *Annulliert am …* for a
				     cancelled seat, whether it attended once the course is
				     closed. --}}
				<x-slot:badges>
					@if ($booking->isCancelled())
						<x-ui.badge variant="danger">Annulliert am {{ $booking->cancelled_at->format('d.m.Y') }}</x-ui.badge>
					@elseif ($event->state === \App\Enums\EventState::Closed)
						<x-course.attendance-badge :booking="$booking" :closed="true" />
					@endif
				</x-slot:badges>

				{{-- **Nothing to cancel once the course is shut.** Legacy hides
				     the button on `event.is_closed`, and the rule underneath is
				     stronger than the screen: cancelling a closed course would
				     fire the 100 % penalty against a seat the student has
				     already sat in ([[CancellationPenalty]]). `isEditable()` is
				     the same window the laptop can be changed in — open until
				     the invoice is raised. --}}
				@if (! $booking->isCancelled() && $event->state !== \App\Enums\EventState::Closed)
					<x-slot:action>
						<x-ui.button variant="danger"
							@click="$store.portal.askCancel({{ \Illuminate\Support\Js::from([
								'uuid' => $booking->uuid,
								'penalty' => $penalty['applies'],
								'amount' => $penalty['amount'],
								'rate' => $penalty['rate'],
							]) }})">
							Annullieren
						</x-ui.button>
					</x-slot:action>

					@if ($booking->has_rental && $booking->isEditable())
						<x-slot:rentalAction>
							<x-ui.button variant="danger"
								@click="$store.portal.askCancelRental({{ \Illuminate\Support\Js::from(['uuid' => $booking->uuid]) }})">
								Annullieren
							</x-ui.button>
						</x-slot:rentalAction>
					@endif
				@endif
			</x-row.event>

			{{-- A cancelled row stays reachable — the booking is history, and its
			     documents still point here. Legacy drops a cancelled booking out
			     of every list, so its only way back here is a link that 404s. The
			     date it was cancelled is the badge above. --}}
		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Nachrichten* — the course thread.

			251 messages over four years and **every one written by an admin or
			an expert**; not a single one from a student-only account
			([[08-accounts]]). So this is a read for the person looking at it,
			and there is no compose box: the endpoint that would take one is
			`POST /api/events/{event}/messages`, gated by [[MessagePolicy]] —
			which is the object-level check legacy's `role:admin,expert,student`
			route never made, and which let any student post to any event and
			mail every participant of it (finding 4).
		--}}
		{{-- **Was an inline rendering, and that was not legacy's** — found
		     2026-09-22 while building the expert screen, which needed the same
		     list. Both portals draw the thread through `messages/Index.vue`, and
		     what it draws is a row with 35 characters of the body and an
		     *Anzeigen* that opens the message in a lightbox
		     ([[x-row.message]]). --}}
		<x-ui.collapsible dashboard title="Nachrichten" :expanded="false" :count="$messages->count()">
			@forelse ($messages as $message)
				<x-row.message :message="$message" />
			@empty
				<x-ui.no-results>Es sind noch keine Nachrichten vorhanden.</x-ui.no-results>
			@endforelse
		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Kurs-Dokumente* — what the expert uploaded for the people on the
			course: zips of models and textures, workshop PDFs.

			**These have been unreachable since the port.** `port:media` wrote 13
			rows with `mediable_type = App\Models\Event` and `Event` carried no
			`media()` relation, so nothing could ask for them — the same silent
			failure `event_expert` had, where an unfilled or unread morph raises
			no error, no null and no missing column ([[Event]]).
		--}}
		{{-- **Was two columns and a size in parentheses, and that was not
		     legacy's either** — same finding as the thread above.
		     `files/components/ListItem.vue` draws four columns: the name, when it
		     was uploaded, how big it is, and the buttons
		     ([[x-row.file]]). The student passes no `action`, so the column
		     holds the *Download* alone; the expert's passes a *Löschen*. --}}
		<x-ui.collapsible dashboard title="Kurs-Dokumente" :expanded="false" :count="$files->count()">
			@forelse ($files as $file)
				<x-row.file :file="$file" />
			@empty
				<x-ui.no-results>Es sind keine Dokumente vorhanden.</x-ui.no-results>
			@endforelse
		</x-ui.collapsible>
	</div>

	<x-dialog.booking />
</x-layout.site>
