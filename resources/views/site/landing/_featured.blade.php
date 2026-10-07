{{--
	*Beliebte Angebote* (the review's homepage markers 5 and 6): the courses
	flagged *Beliebt* in their form, in the catalogue's order, as the Kurse
	page draws them (`x-card.course`), three across from sm as the Vorhaben
	tiles are. The mockup's tiles pair a course with its licence (marker 7);
	licences join when they exist (chunk 05), so for now it is courses, and
	the link beside the heading goes to the Kurse page in place of the
	mockup's *Alle Angebote von A bis Z*.
--}}
<section class="mt-48 lg:mt-64">
	<div class="mb-16 flex flex-wrap items-baseline justify-between gap-x-16 lg:mb-24">
		<h2 class="font-bold">Beliebte Angebote</h2>
		<a href="{{ \App\Support\SiteUrl::courses() }}" class="inline-flex items-center gap-8 text-teal hover:underline">
			Alle Kurse
			<x-icon.arrow-right />
		</a>
	</div>

	<div class="grid grid-cols-12 gap-16 lg:gap-40">
		@foreach ($featured as $course)
			<x-card.course :course="$course" class="col-span-6 sm:col-span-4" />
		@endforeach
	</div>
</section>
