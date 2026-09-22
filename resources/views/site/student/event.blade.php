@php
	$locale = app()->getLocale();
	$title = $event->course->getTranslation('title', $locale);
@endphp

<x-layout.site :title="$title" :heading="$title" auth>
	{{--
		One booked seat — `backend/student/views/Course.vue`.

		Three blocks under the heading: the booking itself, the notes the expert
		or VIAK posted to the course, and the course materials.

		**The heading is black here, not teal.** `article.content-text--event`
		is the one variant of the block that restates `h1 { color: $color-primary }`
		(`layout/_article.scss:140`) — because on this page the heading is the
		course's name rather than the screen's, so it reads as content rather
		than as a label.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold sm:block">{{ $title }}</h1>
			<x-site.back-link :href="\App\Support\SiteUrl::studentPortal()" />
		</x-slot:aside>
	</x-site.article>

	<div class="mt-48 lg:mt-64">
		<x-site.collapsible title="Buchung" :expanded="true">
			<x-site.event-row :event="$event" :booking="$booking">
				{{-- **Nothing to cancel once the course is shut.** Legacy hides
				     the button on `event.is_closed`, and the rule underneath is
				     stronger than the screen: cancelling a closed course would
				     fire the 100 % penalty against a seat the student has
				     already sat in ([[CancellationPenalty]]). `isEditable()` is
				     the same window the laptop can be changed in — open until
				     the invoice is raised. --}}
				@if (! $booking->isCancelled() && $event->state !== \App\Enums\EventState::Closed)
					<x-slot:action>
						<x-site.button variant="secondary"
							@click="$store.portal.askCancel({{ \Illuminate\Support\Js::from([
								'uuid' => $booking->uuid,
								'penalty' => $penalty['applies'],
								'amount' => $penalty['amount'],
								'rate' => $penalty['rate'],
							]) }})">
							Annullieren
						</x-site.button>
					</x-slot:action>

					@if ($booking->has_rental && $booking->isEditable())
						<x-slot:rentalAction>
							<x-site.button variant="secondary"
								@click="$store.portal.askCancelRental({{ \Illuminate\Support\Js::from(['uuid' => $booking->uuid]) }})">
								Annullieren
							</x-site.button>
						</x-slot:rentalAction>
					@endif
				@endif
			</x-site.event-row>

			@if ($booking->isCancelled())
				{{-- The row stays reachable after a cancellation — the booking
				     is history, and its documents still point here. Legacy has
				     no state for this at all: its portal drops a cancelled
				     booking out of every list, so the only way back to this
				     screen is a link that now 404s. --}}
				<p class="mt-16 italic text-danger">
					Diese Buchung wurde am {{ $booking->cancelled_at->format('d.m.Y') }} annulliert.
				</p>
			@endif
		</x-site.collapsible>
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
		<x-site.collapsible title="Nachrichten" :expanded="false" :count="$messages->count()">
			@forelse ($messages as $message)
				<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-4">
							<strong class="font-bold">{{ $message->subject }}</strong><br>
							{{ $message->created_at->format('d.m.Y') }}
							@if ($message->author), {{ $message->author->name }}@endif
						</div>

						<div class="sm:col-span-8">
							<x-site.rich-text :html="$message->body" />

							@foreach ($message->media as $attachment)
								<div class="mt-16">
									<a href="{{ route('media.download', $attachment->uuid) }}"
										class="hover:text-teal">{{ $attachment->original_name }}</a>
								</div>
							@endforeach
						</div>
					</div>
				</article>
			@empty
				<p class="mt-16 italic">Es sind keine Nachrichten vorhanden.</p>
			@endforelse
		</x-site.collapsible>
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
		<x-site.collapsible title="Kurs-Dokumente" :expanded="false" :count="$files->count()">
			@forelse ($files as $file)
				<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="flex items-start justify-between gap-16">
						<div>
							{{ $file->original_name }}
							@if ($file->size)
								<em class="italic">({{ round($file->size / 1024 / 1024, 1) }} MB)</em>
							@endif
						</div>
						<div>
							<x-site.button href="{{ route('media.download', $file->uuid) }}"
								title="{{ $file->original_name }}">Download</x-site.button>
						</div>
					</div>
				</article>
			@empty
				<p class="mt-16 italic">Es sind keine Kurs-Dokumente vorhanden.</p>
			@endforelse
		</x-site.collapsible>
	</div>

	<x-site.booking-dialogs />
</x-layout.site>
