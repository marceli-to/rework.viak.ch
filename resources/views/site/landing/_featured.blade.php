{{--
	*Beliebte Angebote* (the review's homepage markers 5 to 7): courses and
	software **in one grid** (Marcel, 2026-10-09, over the mockup's paired
	tiles and a hand-picked list). Each is flagged *Beliebt* in its own form;
	the courses come first, in the catalogue's order, as the Kurse page draws
	them (`x-card.course`), then the software as the Software page does
	(`x-card.software`), three across from sm as the Vorhaben tiles are.

	The mockup's link is *Alle Angebote von A bis Z*, a page there is not, so
	the two lists it would have joined stand beside the heading instead.
--}}
<section class="mt-48 lg:mt-64">
	<div class="mb-16 flex flex-wrap items-baseline justify-between gap-x-16 lg:mb-24">
		<h2 class="font-bold">Beliebte Angebote</h2>
		<div class="flex flex-wrap gap-x-24">
			<a href="{{ \App\Support\SiteUrl::courses() }}" class="inline-flex items-center gap-8 text-teal hover:underline">
				Alle Kurse
				<x-icon.arrow-right />
			</a>
			<a href="{{ \App\Support\SiteUrl::softwareIndex() }}" class="inline-flex items-center gap-8 text-teal hover:underline">
				Alle Software
				<x-icon.arrow-right />
			</a>
		</div>
	</div>

	<div class="grid grid-cols-12 gap-16 lg:gap-40">
		@foreach ($featured as $course)
			<x-card.course :course="$course" class="col-span-6 sm:col-span-4" />
		@endforeach

		@foreach ($featuredSoftware as $software)
			<x-card.software :software="$software" class="col-span-6 sm:col-span-4" />
		@endforeach
	</div>
</section>
