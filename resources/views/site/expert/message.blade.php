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

		**The body is a `<textarea>` where legacy has TinyMCE.** There is no
		editor on the public site and there will not be one before
		[[07-editor]]; blank lines become paragraphs on the way in
		([[ExpertPortalController::paragraphs]]), which is what the thread and the
		mail render.
	--}}
	<x-site.article>
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

			<x-site.back-link :href="\App\Support\SiteUrl::expertEvent($event->uuid)" />
		</x-slot:aside>

		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		{{-- `multipart/form-data`, because the attachments come with the form
		     rather than having been uploaded ahead of it. The POST lands on this
		     same URL so a validation failure comes back to the form by itself —
		     **minus the files**, which no browser will re-send and none should.
		     Said in the hint rather than left to be discovered. --}}
		<form method="POST" enctype="multipart/form-data"
			action="{{ route($locale.'.expert.event.message.store', ['uuid' => $event->uuid]) }}">
			@csrf

			<x-site.field name="subject" label="Betreff" required />

			<x-site.textarea name="body" label="Nachricht" required :rows="12" />

			{{-- *Anhänge (max. 32 MB)* — legacy's label, and here the number is
			     the rule rather than a sentence beside it
			     ([[PostEventMessageRequest]]). --}}
			<x-site.file-input name="attachments" label="Anhänge (max. 32 MB)"
				hint="Höchstens 10 Dateien. Nach einem Fehler müssen die Anhänge neu gewählt werden." />

			{{-- `.line-after`: a 1px black rule under the group and 32px below. --}}
			<div class="mb-32 border-b border-black pb-16">
				<x-site.checkbox name="copy_to_me" :checked="(bool) old('copy_to_me')">
					Kopie der Nachricht an mich
				</x-site.checkbox>
			</div>

			<div class="mb-16 lg:mb-32">
				<x-site.button type="submit" class="w-full">Senden</x-site.button>
			</div>

			<a href="{{ \App\Support\SiteUrl::expertEvent($event->uuid) }}"
				class="inline-block text-md italic transition-colors hover:text-teal sm:text-lg lg:text-xl">Abbrechen</a>
		</form>
	</x-site.article>
</x-layout.site>
