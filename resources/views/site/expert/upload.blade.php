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

		{{-- `group`, so *Speichern* can read the input's validity: `required`
		     leaves it `:invalid` until a file is in it, and legacy's button is
		     `.is-disabled` — 60% and dead to the pointer — until then. CSS
		     rather than Alpine state, so it holds with no JavaScript as well. --}}
		<form method="POST" enctype="multipart/form-data" class="group"
			action="{{ route($locale.'.expert.event.upload.store', ['uuid' => $event->uuid]) }}">
			@csrf

			{{-- No label, as legacy has none: the box names itself, and the
			     limits are the `.requirements` line under it — legacy's
			     (`shared/modules/files/Index.vue`) plus JPG, PNG and TIFF, all
			     in [[DocumentTypes]], which the server checks too. The ten-file
			     cap is ours ([[UploadEventMediaRequest]]) and is said when it
			     bites. 16px above it, as legacy's `mt-2x` wrapper puts it. --}}
			<div class="pt-16">
				<x-site.file-input name="files" required
					:accept="\App\Support\DocumentTypes::accept()"
					:restrictions="\App\Support\DocumentTypes::RESTRICTIONS"
					:max-size="32" :max-files="10" />
			</div>

			{{-- `.line-before`: a 1px black rule **above** the group — the
			     mirror of the `.line-after` the other forms carry, and legacy
			     puts it here rather than there because this form has nothing
			     under the rule but the button. The space over it is the file
			     control's own; 12px under it, 24 from `lg`, measured. --}}
			<div class="border-t border-black pt-12 lg:pt-24">
				{{-- No *Abbrechen*: legacy's `Files.vue` has none, and *Zurück*
				     in the aside already goes where it would. --}}
				<div class="mb-16 lg:mb-32">
					<x-site.button type="submit" class="w-full group-has-[:invalid]:pointer-events-none group-has-[:invalid]:opacity-60">Speichern</x-site.button>
				</div>
			</div>
		</form>
	</x-site.article>
</x-layout.site>
