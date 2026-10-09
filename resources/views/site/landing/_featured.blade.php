{{--
	*Beliebte Angebote* (the review's homepage markers 5 to 7): courses and
	software **in one grid** (Marcel, 2026-10-09, over the mockup's paired
	tiles and a hand-picked list). Each is flagged *Beliebt* in its own form;
	the courses come first, in the catalogue's order, as the Kurse page draws
	them (`x-card.course`), then the software as the Software page does
	(`x-card.software`), three across from sm as the Vorhaben tiles are. Each
	card says *Kurs* or *Software* in its top right corner, since a software's
	card and its course's can carry the same picture.

	**No link beside the heading for now** (Marcel, 2026-10-09). The mockup's
	is *Alle Angebote von A bis Z*, a page there is not; *Alle Kurse* and
	*Alle Software* stood there for a day.
--}}
<section class="mt-48 lg:mt-64">
	<h2 class="mb-16 font-bold lg:mb-24">Beliebte Angebote</h2>

	<div class="grid grid-cols-12 gap-16 lg:gap-40">
		@foreach ($featured as $course)
			<x-card.course :course="$course" kind="Kurs" class="col-span-6 sm:col-span-4" />
		@endforeach

		@foreach ($featuredSoftware as $software)
			<x-card.software :software="$software" kind="Software" class="col-span-6 sm:col-span-4" />
		@endforeach
	</div>
</section>
