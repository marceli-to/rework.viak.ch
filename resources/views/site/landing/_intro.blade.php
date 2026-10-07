{{--
	Legacy's intro (`web/pages/home/index.blade.php`), rebuilt 1:1: an
	`article.content-text-media is-reverse` (`layout/_article.scss`), all of
	it teal. *Ihre Zukunft ist visuell* in the span-4 aside, the copy bold in
	the span-8 column, then the hero images as a slider.

	`is-reverse` puts the text **above** the images from sm, 48px between
	them (`mb-12x`); on a phone the source order stands, the images first
	and 24px under them (`mb-6x`). The copy is legacy's, from its `__()`
	strings, verbatim. The images are legacy's home hero, kept on
	`Page::for('home')` and edited in *Seiteninhalte → Startseite*.
--}}
<article class="text-teal sm:flex sm:flex-col">
	@if ($slides->isNotEmpty())
		<x-media.slider :images="$slides" alt="Visualisierungs-Akademie" class="mb-24 sm:order-2 sm:mb-0" />
	@endif

	<div class="sm:order-1 sm:mb-48 sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<aside class="mb-12 sm:col-span-4">
			<h1 class="font-bold">Ihre Zukunft ist visuell</h1>
		</aside>

		<div class="font-bold sm:col-span-8 [&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0">
			<p>Visualisieren ist die Schlüsselkompetenz der Zukunft.<br>Wir machen Sie fit: zum Beispiel in unseren vielseitigen Kursen oder massgeschneiderten Individualschulungen.</p>
			<p>Wir sind führend, wenn es um Visualisierung geht.</p>
		</div>
	</div>
</article>
