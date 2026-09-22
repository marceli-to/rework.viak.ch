<x-layout.site title="Dokumente" heading="Meine Dokumente" auth>
	{{--
		*Meine Dokumente* — `backend/student/views/Documents.vue`, the full list
		behind the profile's five.

		Every row's *Download* goes through [[DocumentController]] and
		[[UserDocumentPolicy]]. On the live site the same links are public paths
		under the `storage` symlink, so all 1,162 documents are fetchable without
		authenticating ([[08-accounts]], finding 3) — and **271 of them 404
		anyway**, because their stored `uri` is missing the separator between
		`files` and the user's uuid. 95 students have been looking at broken
		links since 2023; the path is derived rather than stored here, so it
		cannot acquire a typo ([[UserDocument::path]]).
	--}}
	<x-site.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Meine Dokumente</h1>
			<x-site.back-link :href="\App\Support\SiteUrl::studentPortal()" />
		</x-slot:aside>
	</x-site.article>

	<div class="mt-48 lg:mt-64">
		<x-site.collapsible title="Dokumente" :expanded="true" :count="$documents->count()">
			@forelse ($documents as $document)
				<x-site.document-row :document="$document" />
			@empty
				<p class="mt-16 italic">Es sind noch keine Dokumente vorhanden.</p>
			@endforelse
		</x-site.collapsible>
	</div>
</x-layout.site>
