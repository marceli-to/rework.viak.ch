{{--
	*Zahlung per Kreditkarte* — **a placeholder** for legacy's
	`/de/zahlung/rechnung/{uuid}`, the Stripe page the course confirmation mail
	links to ([[SiteUrl::invoicePayment]]). Not rebuilt yet; tracked in
	`Todo.md` and `Open-Questions.md` #28. Drawn as the Kontakt page is: the
	heading in the aside, the text in the column.
--}}
<x-layout.site title="Zahlung per Kreditkarte" heading="Zahlung">
	<article class="mb-48 sm:grid sm:grid-cols-12 sm:gap-16 lg:mb-64 lg:gap-40">
		<aside class="max-sm:hidden sm:col-span-4">
			<h1 class="font-bold text-teal">Zahlung per Kreditkarte</h1>
		</aside>
		<div class="sm:col-span-8">
			<p class="mb-16">Die Zahlung per Kreditkarte ist im Moment nicht möglich.</p>
			<p class="mb-16">Bitte bezahle die Rechnung mit dem QR-Einzahlungsschein, der ihr beiliegt. Bei Fragen erreichst du uns über die <a href="{{ \App\Support\SiteUrl::contact() }}" class="underline">Kontaktseite</a>.</p>
		</div>
	</article>
</x-layout.site>
