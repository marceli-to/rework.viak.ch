<x-layout.site
	title="Kurse"
	description="Kurse für digitales Gestalten — Modellieren, Visualisieren, Animieren, Editieren."
>
	<x-site.breadcrumb :items="['Home' => \App\Support\SiteUrl::home(), 'Kurse' => null]" />

	<div class="mx-auto max-w-(--container-site) px-4 pb-20 sm:px-8">
		<div class="border-b border-line py-10">
			<h1 class="text-4xl font-semibold tracking-tight text-ink">Kurse</h1>
			<p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-muted">
				Kurse für digitales Gestalten — Modellieren, Visualisieren, Animieren, Editieren.
				Unterrichtet von Leuten, die damit täglich arbeiten.
			</p>
		</div>

		<div class="grid gap-8 py-10 lg:grid-cols-[220px_1fr]">
			<aside>
				<h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-faint">Software</h2>
				<ul class="flex flex-wrap gap-1.5 lg:flex-col lg:gap-1">
					@foreach ($software as $item)
						<li>
							<a href="{{ request()->fullUrlWithQuery(['software' => $item->uuid === $activeSoftware ? null : $item->uuid]) }}"
							   @class([
								   'block px-2 py-1 text-[13px] transition',
								   'bg-teal text-white' => $item->uuid === $activeSoftware,
								   'text-muted hover:text-teal-dark' => $item->uuid !== $activeSoftware,
							   ])>
								{{ $item->getTranslation('title', app()->getLocale()) }}
							</a>
						</li>
					@endforeach
				</ul>
			</aside>

			<div>
				@if ($courses->isEmpty())
					<p class="text-sm text-muted">Für diese Auswahl sind keine Kurse verfügbar.</p>
				@else
					<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
						@foreach ($courses as $course)
							<x-site.course-card :course="$course" />
						@endforeach
					</div>
				@endif
			</div>
		</div>
	</div>
</x-layout.site>
