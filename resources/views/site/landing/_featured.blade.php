{{--
	*Beliebte Angebote* (the review's homepage markers 5 to 7), **the
	mockup's paired tiles** (`card/angebot.blade.php`), as the client asked on
	2026-10-26: one per Angebot, *Kurs ab* and *Lizenz ab* side by side. This
	reverses Marcel's one grid of separate course and software cards of
	2026-10-09 ([[14-angebote]]).

	An Angebot is here when it is flagged *Beliebt* or one of its courses is
	([[Software::scopePopular]]). A flagged course no Angebot holds has the
	course card it had. Three across from sm. **The heading links to *Alle
	Angebote***, the mockup's *Alle Angebote von A bis Z*, now that the page
	exists.
--}}
<section class="mt-48 lg:mt-64">
	<div class="mb-16 flex flex-wrap items-baseline justify-between gap-x-16 lg:mb-24">
		<h2 class="font-bold">Beliebte Angebote</h2>
		<a href="{{ \App\Support\SiteUrl::offers() }}" class="text-teal hover:text-black">Alle Angebote von A bis Z</a>
	</div>

	<div class="grid grid-cols-12 gap-16 lg:gap-40">
		@foreach ($popular as $software)
			<x-card.angebot :software="$software" class="col-span-6 sm:col-span-4" />
		@endforeach

		@foreach ($featured as $course)
			<x-card.course :course="$course" kind="Kurs" class="col-span-6 sm:col-span-4" />
		@endforeach
	</div>
</section>
