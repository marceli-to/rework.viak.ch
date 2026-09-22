@php
	$locale = app()->getLocale();
	$title = $event->course->getTranslation('title', $locale);
	$cancelled = $event->state === \App\Enums\EventState::Cancelled;
@endphp

<x-layout.site :title="$title" :heading="$title" auth>
	{{--
		One course an expert teaches — `backend/expert/views/Course.vue`
		([[08-accounts]], [[09-public-site]]).

		Four collapsibles: *Informationen*, *Teilnehmer*, *Nachrichten*,
		*Kurs-Dokumente*. The student's booked-event screen is the same four with
		the first two replaced by the booking, which is the whole difference
		between owning a seat and running the room.

		**The heading is black, not teal**, as it is on the student's: the
		`article.content-text--event` variant restates `h1 { color: $color-primary }`
		because here the heading is the course's name rather than the screen's.

		**Every block on this page is behind an object-level check.** Legacy gates
		the lot with `role:admin,expert` — so any of the 18 accounts holding the
		Expert role can open any course in the archive and download the contact
		details of everyone on it ([[08-accounts]], finding 5). The controller
		asks [[EventPolicy::viewParticipants]] once, before the screen exists.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold sm:block">{{ $title }}</h1>
			<x-site.back-link :href="\App\Support\SiteUrl::expertPortal()" />
		</x-slot:aside>
	</x-site.article>

	@if (session('status'))
		<div class="mt-48 lg:mt-64">
			<x-site.toast variant="success">{{ session('status') }}</x-site.toast>
		</div>
	@endif

	<div class="mt-48 lg:mt-64">
		{{-- *Informationen* — the row, without a fee and without *mit …*: what
		     the course costs is the student's question, and naming the expert on
		     the expert's own screen is noise. --}}
		<x-site.collapsible title="Informationen" :expanded="true">
			<x-site.event-row :event="$event" :showExperts="false" :showFee="false"
				:bookings="$bookings->count()" />
		</x-site.collapsible>
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
		<x-site.collapsible title="Teilnehmer" :expanded="false" :count="$bookings->count()">
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
					{{-- `sm:span-4 md:span-3` and two `sm:span-2 md:span-3`,
					     which is legacy's and adds up to 8 of 12 at `sm` and 9
					     at `md`. The columns are left-aligned rather than
					     spread, so the shortfall is trailing space — not a bug
					     to tidy, and tidying it would widen a name column that
					     is already wide enough. --}}
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-4 lg:col-span-3">{{ $booking->user->name }}</div>
						<div class="sm:col-span-2 lg:col-span-3">{{ $booking->user->city }}</div>
						<div class="sm:col-span-2 lg:col-span-3">{{ $company }}</div>
					</div>

					@if ($booking->isCancelled())
						{{-- Only ever seen on a cancelled course, where the list
						     *is* the cancelled seats. Legacy prints nothing and
						     the expert cannot tell the two states apart. --}}
						<div class="text-danger">annulliert</div>
					@endif
				</article>
			@empty
				<p class="mt-16 italic">Es sind keine Anmeldungen für diesen Kurs vorhanden.</p>
			@endforelse

			{{--
				*Teilnehmerliste (PDF)* — the printable form of the list above.

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
				{{-- `.mt-5x sm:mt-10x` above it, and the same arrow-below link
				     the aside's *Zurück* uses. --}}
				<div class="mt-20 sm:mt-40">
					<a href="{{ route($locale.'.expert.event.participants', ['uuid' => $event->uuid]) }}"
						title="Teilnehmerliste herunterladen"
						class="inline-block text-left transition-colors hover:text-teal">
						<span class="mb-4 block">Teilnehmerliste (PDF)</span>
						<x-icon.arrow-right />
					</a>
				</div>
			@endif

		</x-site.collapsible>
	</div>

	<div class="mt-48 lg:mt-64">
		{{--
			*Nachrichten* — the course thread, and the one screen on the public
			site that can write to it.

			Legacy hides this block entirely when nobody is booked
			(`v-if="data.participants.length"`), which is a reasonable thing to
			do with a form that mails a list of nought — kept.
		--}}
		<x-site.collapsible title="Nachrichten" :expanded="false" :count="$messages->count()">
			@forelse ($messages as $message)
				<x-site.message-row :message="$message" />
			@empty
				<p class="mt-16 italic">Es sind keine Nachrichten vorhanden.</p>
			@endforelse

			@if ($bookings->isNotEmpty() && ! $cancelled)
				{{-- `.flex.justify-start.mt-6x` around a 16×16 plus, the same
				     control the student's address list adds with. --}}
				<div class="mt-24 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::expertEventMessage($event->uuid) }}"
						title="Nachricht erstellen"
						class="block transition-colors hover:text-teal">
						<x-icon.plus class="w-16" />
					</a>
				</div>
			@endif
		</x-site.collapsible>
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
		<x-site.collapsible title="Kurs-Dokumente" :expanded="false" :count="$files->count()">
			@forelse ($files as $file)
				<x-site.file-row :file="$file">
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
						<x-site.button variant="secondary"
							@click="$store.confirm.ask({{ \Illuminate\Support\Js::from('delete-file-'.$file->uuid) }})">Löschen</x-site.button>

						<form method="POST" id="delete-file-{{ $file->uuid }}" class="hidden"
							action="{{ route($locale.'.expert.event.file.destroy', ['uuid' => $event->uuid, 'media' => $file->uuid]) }}">
							@csrf
							@method('DELETE')
						</form>
					</x-slot:action>
				</x-site.file-row>
			@empty
				<p class="mt-16 italic">Es sind keine Dokumente vorhanden.</p>
			@endforelse

			@if (! $cancelled)
				<div class="mt-24 flex justify-start">
					<a href="{{ \App\Support\SiteUrl::expertEventUpload($event->uuid) }}"
						title="Dokumente hochladen"
						class="block transition-colors hover:text-teal">
						<x-icon.plus class="w-16" />
					</a>
				</div>
			@endif
		</x-site.collapsible>
	</div>

	{{-- The one confirmation this screen asks — one dialog per page, told which
	     form to submit ([[x-site.confirm-dialog]]). --}}
	<x-site.confirm-dialog />
</x-layout.site>
