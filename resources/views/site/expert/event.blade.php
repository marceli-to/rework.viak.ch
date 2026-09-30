@php
	$locale = app()->getLocale();
	$title = $event->course->getTranslation('title', $locale);
	// The dashboard's heading for the same event: the course number before the
	// name ([[Event/Show]]). The browser tab keeps the bare name.
	$heading = $event->course->number.' '.$title;
	$cancelled = $event->state === \App\Enums\EventState::Cancelled;
@endphp

<x-layout.site :title="$title" :heading="$heading" auth>
	{{--
		One course an expert teaches — `backend/expert/views/Course.vue`
		([[08-accounts]], [[09-public-site]]).

		Four collapsibles: *Informationen*, *Teilnehmer*, *Nachrichten*,
		*Kurs-Dokumente*. The student's booked-event screen is the same four with
		the first two replaced by the booking, which is the whole difference
		between owning a seat and running the room.

		**The heading is the dashboard's**: teal, with the course number
		(Marcel, 2026-09-30). Legacy drew it black and without the number.

		**Every block on this page is behind an object-level check.** Legacy gates
		the lot with `role:admin,expert` — so any of the 18 accounts holding the
		Expert role can open any course in the archive and download the contact
		details of everyone on it ([[08-accounts]], finding 5). The controller
		asks [[EventPolicy::viewParticipants]] once, before the screen exists.
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">{{ $heading }}</h1>
			<x-ui.back-link :href="\App\Support\SiteUrl::expertPortal()" />
		</x-slot:aside>
	</x-layout.article>

	@if (session('status'))
		<div class="mt-48 lg:mt-64">
			<x-ui.toast variant="success">{{ session('status') }}</x-ui.toast>
		</div>
	@endif

	<div class="mt-48 lg:mt-64">
		{{-- *Informationen* — the row, without a fee and without *mit …*: what
		     the course costs is the student's question, and naming the expert on
		     the expert's own screen is noise. --}}
		<x-ui.collapsible dashboard title="Informationen" :expanded="true">
			<x-row.event :event="$event" :showExperts="false" :showFee="false"
				:bookings="$bookings->count()" :rentals="$bookings->where('has_rental', true)->count()" />
		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Teilnehmer* — name, town, and the firm the seat is billed to.

			**Where the course is cancelled these are the cancelled bookings**,
			which is legacy's own `$event->isCancelled() ? $event->cancelledBookings
			: $event->bookings` and is right: calling off a course cancels every
			seat on it, so the live list would be empty and the expert would lose
			the list of people they have to apologise to
			([[ExpertPortalController::event]]).

			**No email address and no phone number on the screen**, which is
			legacy's shape too — `EventParticipantsResource` hands the address
			out only to an admin, and the expert's screen never renders it. The
			contact details are in the participant list, and the participant list
			is the one thing here that is not built ([[09-public-site]]).
		--}}
		<x-ui.collapsible dashboard title="Teilnehmer" :expanded="true" :count="$bookings->count()">
			@forelse ($bookings as $booking)
				@php
					// The firm, from the seat's own record first and the invoice
					// address second — legacy's `participant.company ??
					// participant.invoice_address.company`, and in that order,
					// because somebody who typed a firm into their profile meant
					// it about themselves.
					$company = $booking->user->company
						?: $booking->user->addresses->first(fn ($address) => filled($address->company))?->company;
				@endphp

				<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					{{-- Name, town and firm, then the seat's badges against the far
					     edge as on the dashboard's event page (Marcel, 2026-09-29):
					     the laptop, and whether the seat attended — asked when the
					     course is closed ([[EventPageController::close]]). A seat on
					     a cancelled course says so, where legacy printed nothing and
					     the expert could not tell the two states apart. --}}
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-2">{{ $booking->user->name }}</div>
						<div class="sm:col-span-2">{{ $booking->user->city }}</div>
						<div class="sm:col-span-2">{{ $company }}</div>
						{{-- The dashboard's email column stays empty: the expert
						     does not get the address (see above). --}}
						<div class="mt-8 flex flex-wrap items-start gap-8 sm:col-span-6 sm:mt-0 sm:justify-end">
							@if ($booking->has_rental)
								<x-ui.badge>Mietcomputer</x-ui.badge>
							@endif
							@if ($booking->isCancelled())
								<x-ui.badge variant="danger">Annulliert</x-ui.badge>
							@else
								<x-course.attendance-badge :booking="$booking" :closed="$event->state === \App\Enums\EventState::Closed" />
							@endif
						</div>
					</div>
				</article>
			@empty
				<x-ui.no-results>Es sind keine Anmeldungen für diesen Kurs vorhanden.</x-ui.no-results>
			@endforelse

			{{--
				*Teilnehmerliste* — the printable form of the list above.

				**Behind the same check as the screen** ([[EventPolicy::viewParticipants]]),
				which is what legacy's own route does not have: it carries
				`role:admin,expert` and nothing else, so any of the 18 accounts
				holding the Expert role could download the names, towns, phone
				numbers and email addresses of every student on every course in
				the archive (`08-accounts.md`, finding 5).

				Hidden on a cancelled course, as legacy hides it —
				`v-if="!data.event.is_cancelled"`.
			--}}
			@if ($bookings->isNotEmpty() && ! $cancelled)
				{{-- The dashboard's: the label with the download icon beside it,
				     against the right edge (Marcel, 2026-09-29). Legacy's was
				     *Teilnehmerliste (PDF)* over an arrow. --}}
				<div class="mt-24 flex justify-end sm:mt-48">
					<a href="{{ route($locale.'.expert.event.participants', ['uuid' => $event->uuid]) }}"
						title="Teilnehmerliste herunterladen"
						class="flex items-center gap-12 transition-colors hover:text-teal">
						<span>Teilnehmerliste</span>
						<x-icon.download class="size-18" />
					</a>
				</div>
			@endif

		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Nachrichten* — the course thread, and the one screen on the public
			site that can write to it.

			Legacy hides this block entirely when nobody is booked
			(`v-if="data.participants.length"`), which is a reasonable thing to
			do with a form that mails a list of nought — kept.
		--}}
		<x-ui.collapsible dashboard title="Nachrichten" :expanded="false" :count="$messages->count()">
			@forelse ($messages as $message)
				<x-row.message :message="$message" />
			@empty
				<x-ui.no-results>Es sind noch keine Nachrichten vorhanden.</x-ui.no-results>
			@endforelse

			@if ($bookings->isNotEmpty() && ! $cancelled)
				{{-- `.flex.justify-start.mt-6x` around the dashboard's 20×20 plus. --}}
				<div class="mt-24 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::expertEventMessage($event->uuid) }}"
						title="Nachricht erstellen"
						class="block transition-colors hover:text-teal">
						<x-icon.plus size="large" />
					</a>
				</div>
			@endif
		</x-ui.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Kurs-Dokumente* — what the expert uploads for the people on the
			course: zips of models and textures, workshop PDFs.

			**Every one of these is deletable**, where legacy hides the button on
			`belongs_to_message == false`. That flag is always false in this list:
			its `fileables` pivot keeps event files and message files as disjoint
			sets — 13 and 20 rows, measured — so the guard has never hidden
			anything. In the rework it cannot arise at all, because a `media` row
			has one owner ([[MediaPolicy::delete]]).
		--}}
		<x-ui.collapsible dashboard title="Kurs-Dokumente" :expanded="false" :count="$files->count()">
			@forelse ($files as $file)
				<x-row.file :file="$file">
					<x-slot:action>
						{{-- **A form, and it asks first.** Legacy's *Löschen* is
						     an `<a>` that opens a `<notification>` and then
						     DELETEs over axios; here the button opens the same
						     confirmation and the confirmation submits a real
						     form, so the delete is a POST with a token rather
						     than a link a prefetcher can fire.

						     `btn-secondary` is legacy's own choice of colour for
						     it — grey, not the danger red the address screen's
						     *Löschen* carries. Ported as found. --}}
						<x-ui.button variant="secondary"
							@click="$store.confirm.ask({{ \Illuminate\Support\Js::from('delete-file-'.$file->uuid) }})">Löschen</x-ui.button>

						<form method="POST" id="delete-file-{{ $file->uuid }}" class="hidden"
							action="{{ route($locale.'.expert.event.file.destroy', ['uuid' => $event->uuid, 'media' => $file->uuid]) }}">
							@csrf
							@method('DELETE')
						</form>
					</x-slot:action>
				</x-row.file>
			@empty
				<x-ui.no-results>Es sind keine Dokumente vorhanden.</x-ui.no-results>
			@endforelse

			@if (! $cancelled)
				<div class="mt-24 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::expertEventUpload($event->uuid) }}"
						title="Dokumente hochladen"
						class="block transition-colors hover:text-teal">
						<x-icon.plus size="large" />
					</a>
				</div>
			@endif
		</x-ui.collapsible>
	</div>

	{{-- The one confirmation this screen asks — one dialog per page, told which
	     form to submit ([[x-ui.confirm-dialog]]). --}}
	<x-ui.confirm-dialog />
</x-layout.site>
