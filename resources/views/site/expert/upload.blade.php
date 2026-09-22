@php
	$locale = app()->getLocale();
@endphp

<x-layout.site title="Dokumente hochladen" heading="Dokumente hochladen" auth>
	{{--
		*Dokumente hochladen* — `backend/expert/views/Files.vue`
		([[08-accounts]], [[09-public-site]]).

		The course materials an expert gives the people on the course: zips of
		models and textures, workshop PDFs. A screen of its own under the course,
		as legacy has it, and the shortest form on the site — one control and a
		*Speichern*.

		Behind [[MediaPolicy::createForEvent]], which is the same rule as posting
		a note. Legacy's `EventFileController::store` has no `authorize()` at all
		and its route is `role:admin,expert`, so any of the 18 accounts holding
		the Expert role can add a file to any course in the archive.
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Dokumente hochladen</h1>

			<x-site.back-link :href="\App\Support\SiteUrl::expertEvent($event->uuid)" />
		</x-slot:aside>

		@if ($errors->any())
			<x-site.toast>Es ist ein Fehler aufgetreten.</x-site.toast>
		@endif

		<form method="POST" enctype="multipart/form-data"
			action="{{ route($locale.'.expert.event.upload.store', ['uuid' => $event->uuid]) }}">
			@csrf

			<x-site.file-input name="files" label="Dokumente (max. 32 MB pro Datei)"
				hint="Höchstens 10 Dateien." />

			{{-- `.line-before`: a 1px black rule **above** the group, 32px of
			     space over it — the mirror of the `.line-after` the other forms
			     carry, and legacy puts it here rather than there because this
			     form has nothing under the rule but the button. --}}
			<div class="mt-32 border-t border-black pt-16">
				<div class="mb-16 lg:mb-32">
					<x-site.button type="submit" class="w-full">Speichern</x-site.button>
				</div>

				<a href="{{ \App\Support\SiteUrl::expertEvent($event->uuid) }}"
					class="inline-block text-md italic transition-colors hover:text-teal sm:text-lg lg:text-xl">Abbrechen</a>
			</div>
		</form>
	</x-site.article>
</x-layout.site>
