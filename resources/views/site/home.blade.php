<x-layout.site
	title="Visualisierungs-Akademie"
	description="Kurse in Visualisierung, Architekturvisualisierung und 3D für die Praxis."
>
	<section class="mx-auto max-w-(--container-site) px-4 py-16 sm:px-8">
		<h1 class="max-w-3xl text-3xl leading-tight font-semibold tracking-tight text-ink sm:text-4xl">
			Visualisierungs-Akademie
		</h1>
		<p class="mt-4 max-w-2xl text-lg text-muted">
			Kurse in Visualisierung, Architekturvisualisierung und 3D — von Leuten,
			die damit arbeiten.
		</p>
		<a href="{{ \App\Support\SiteUrl::courses() }}"
			class="mt-8 inline-block bg-teal px-5 py-2.5 font-medium text-white hover:bg-teal-dark">
			Kurse ansehen
		</a>
	</section>
</x-layout.site>
