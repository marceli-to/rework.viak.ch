@php
	$locale = app()->getLocale();
@endphp

<x-layout.site title="Nachricht erstellen" heading="Nachricht erstellen" auth>
	{{--
		*Nachricht erstellen* — `backend/expert/views/Messages.vue`
		([[08-accounts]], [[09-public-site]]).

		A screen of its own under the course, as legacy has it, and the only place
		on the public site that writes to a course's thread. The rule underneath
		is [[MessagePolicy::create]]: the experts who teach this course and
		admins. Legacy's is `role:admin,expert,student` with a FormRequest that
		authorises everything, so any authenticated student could post to any
		course and mail every participant of it (finding 4).

		**The body is a tiptap editor in TinyMCE's clothes** ([[x-form.editor]]),
		with three of legacy's eight buttons: none of the 255 messages legacy
		holds uses any formatting (Marcel, 2026-09-23). Its HTML is cleaned on
		the way in ([[MessageHtml]]); without JavaScript it is the textarea
		underneath, and blank lines become paragraphs
		([[ExpertPortalController::paragraphs]]).
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Nachricht erstellen</h1>

			{{-- `.text-small` with 12px above it from `sm` — legacy's
			     `text-small sm:mt-3x`. The sentence promises a send, so it says
			     how many people that is rather than leaving *alle* to be
			     guessed; a course with cancellations reaches fewer people than
			     it has bookings ([[PostMessage]]). --}}
			<p class="text-md sm:mt-12 sm:text-lg lg:text-xl">
				Sende eine Nachricht an alle Studenten dieses Kurses ({{ $recipients }}).
			</p>

			<x-ui.back-link :href="\App\Support\SiteUrl::expertEvent($event->uuid)" />
		</x-slot:aside>

		@if ($errors->any())
			<x-ui.toast>Es ist ein Fehler aufgetreten.</x-ui.toast>
		@endif

		{{-- `multipart/form-data`, because the attachments come with the form
		     rather than having been uploaded ahead of it. The POST lands on this
		     same URL so a validation failure comes back to the form by itself —
		     **minus the files**, which no browser will re-send and none should.
		     Said in the hint rather than left to be discovered. --}}
		<form method="POST" enctype="multipart/form-data"
			action="{{ route($locale.'.expert.event.message.store', ['uuid' => $event->uuid]) }}">
			@csrf

			<x-form.field name="subject" label="Betreff" required />

			<x-form.editor name="body" label="Nachricht" required />

			{{-- *Anhänge (max. 32 MB)* — legacy's label, and here the number is
			     the rule rather than a sentence beside it
			     ([[PostEventMessageRequest]]). --}}
			{{-- No hint line, as legacy has none: the ten-file cap is said by
			     the drop box when it bites. The one thing a visitor could not
			     otherwise know — that a failed send drops the chosen files —
			     is said only when it has just happened. --}}
			<x-form.file-input name="attachments" label="Anhänge" rule
				:accept="\App\Support\DocumentTypes::accept()"
				:restrictions="\App\Support\DocumentTypes::RESTRICTIONS"
				:max-size="32" :max-files="10"
				:hint="$errors->any() ? 'Bitte die Anhänge neu wählen.' : null" />

			{{-- `.line-after`: a 1px black rule under the group, and the space
			     over it is `.form-group__checkbox`'s bottom margin plus the
			     group's own padding — measured 2026-09-23 at 500 / 800 / 1200
			     as 22 / 26 / 36 in all. --}}
			<div class="mb-16 border-b border-black pb-22 sm:pb-26 lg:mb-32 lg:pb-36">
				<x-form.checkbox name="copy_to_me" :checked="(bool) old('copy_to_me')">
					Kopie der Nachricht an mich
				</x-form.checkbox>
			</div>

			{{-- No *Abbrechen*: legacy's composer has none, and *Zurück* in
			     the aside already goes where it would. --}}
			<div class="mb-16 lg:mb-32">
				<x-ui.button type="submit" class="w-full">Senden</x-ui.button>
			</div>
		</form>
	</x-layout.article>
</x-layout.site>
