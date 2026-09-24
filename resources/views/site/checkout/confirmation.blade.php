<x-layout.site title="Buchung abgeschlossen" auth>
	{{--
		`web/pages/checkout/confirmation.blade.php`, rebuilt 1:1 — and unlike the
		four steps this one really is a Blade page on the live site too.

		`article.content-text` with both halves this time, so
		`<x-layout.article>` rather than the bare aside the steps use: the heading
		and *Zum Profil* in the `span-4`, the thank-you in the `span-8`.
	--}}
	<x-layout.article>
		<x-slot:aside>
			<h1 class="hidden font-bold text-teal sm:block">Buchung abgeschlossen</h1>

			{{-- `sm:mt-5x md:mt-10x` — 20px, then 40 from `lg`. The label sits
			     above the arrow, which is legacy's `icon-arrow-right:below`. --}}
			<div class="mt-20 lg:mt-40">
				<a href="{{ \App\Support\SiteUrl::studentPortal() }}" title="Zum Profil"
					class="inline-flex flex-col items-start hover:text-teal">
					<span>Zum Profil</span>
					<x-icon.arrow-right class="mt-8" />
				</a>
			</div>
		</x-slot:aside>

		{{--
			**Emptying the basket is the browser's job, and nothing else can do
			it.** The selection is in `localStorage`, so the server cannot clear
			it on the way past — this page tells Alpine to, once, on arrival.

			Legacy never had to: `BasketStore` was a session bag that a
			completed checkout simply dropped. That is also why nothing in the
			old data records that two bookings were one purchase, which is what
			[[Checkout]] exists to fix ([[06-bookings]]).
		--}}
		<div x-data x-init="$store.basket.clear()">
			<p>Vielen Dank für Deine Buchung. Du erhältst in den nächsten Minuten eine
				Bestätigung per E-Mail.</p>

			<p class="mt-16">
				<a href="{{ \App\Support\SiteUrl::courses() }}" title="Zum Kursangebot"
					class="underline decoration-1 underline-offset-[3px] hover:no-underline">
					Zum Kursangebot
				</a>
			</p>
		</div>
	</x-layout.article>
</x-layout.site>
