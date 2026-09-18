<x-layout.site :title="$course->getTranslation('title', app()->getLocale()).' – Visualisierungs-Akademie'">
	<x-site.breadcrumb :items="[
		'Home' => \App\Support\SiteUrl::home(),
		'Kurse' => \App\Support\SiteUrl::courses(),
		$course->getTranslation('title', app()->getLocale()) => null,
	]" />

	<div class="mx-auto max-w-(--container-site) px-4 pb-20 sm:px-8">
		<div class="border-b border-gray-400 py-10">
			<h1 class="max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-black">
				{{ $course->getTranslation('title', app()->getLocale()) }}
			</h1>
			@if ($subtitle = $course->getTranslation('subtitle', app()->getLocale(), false))
				<p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-gray-600">{{ $subtitle }}</p>
			@endif
		</div>

		<div class="grid gap-12 py-10 lg:grid-cols-[1fr_320px]">
			<div class="space-y-6">
				@if ($summary = $course->getTranslation('summary', app()->getLocale(), false))
					<p class="text-base leading-relaxed text-black">{{ strip_tags($summary) }}</p>
				@endif

				@foreach (['short_description', 'full_description', 'information_content'] as $field)
					@if ($html = $course->getTranslation($field, app()->getLocale(), false))
						<div class="course-copy text-[15px] leading-relaxed text-gray-600">
							{!! \App\Support\RichText::render($html) !!}
						</div>
					@endif
				@endforeach

				@if ($course->facts)
					<div class="grid gap-4 border-t border-gray-400 pt-6 sm:grid-cols-3">
						@foreach ($course->facts as $fact)
							@php($text = is_array($fact) ? ($fact[app()->getLocale()] ?? reset($fact)) : $fact)
							@if (filled($text))
								{{-- Editor HTML, like the description fields above it. Escaped
								     with `{{ }}` it renders as literal <strong> tags, which is
								     what the ported rows actually contain. --}}
								<div class="course-copy border-l-2 border-teal pl-3 text-xs leading-relaxed text-black">
									{!! \App\Support\RichText::render($text) !!}
								</div>
							@endif
						@endforeach
					</div>
				@endif
			</div>

			<aside class="space-y-4">
				<div class="border border-gray-400 p-5">
					<div class="text-2xl font-semibold text-black">
						CHF {{ number_format((float) $course->fee, 0, '.', "'") }}.–
					</div>
					<p class="mt-1 text-xs text-gray-400">pro Person, inkl. Kursunterlagen</p>
				</div>

				<div class="border border-gray-400">
					<h2 class="border-b border-gray-400 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
						Termine
					</h2>

					@forelse ($course->events as $event)
						<div class="flex items-start justify-between gap-3 border-b border-gray-400 px-5 py-4 last:border-b-0">
							<div>
								<div class="text-sm font-semibold text-teal">
									{{ $event->dates->map(fn ($d) => $d->date->format('d.m.'))->implode(' · ') }}{{ $event->date->format('Y') }}
								</div>
								<div class="mt-0.5 text-xs text-gray-400">
									{{ $event->location?->getTranslation('description', app()->getLocale()) ?? 'Ort folgt' }}
									@if ($event->state === \App\Enums\EventState::Confirmed)
										<span class="ml-1 text-success">· durchführungsgarantiert</span>
									@endif
								</div>
							</div>
							<span class="shrink-0 text-xs font-semibold text-black">
								CHF {{ number_format((float) $event->fee(), 0, '.', "'") }}.–
							</span>
						</div>
					@empty
						<p class="px-5 py-4 text-xs text-gray-600">
							Zurzeit keine Termine ausgeschrieben. Wir informieren gerne über die nächsten Daten.
						</p>
					@endforelse
				</div>
			</aside>
		</div>
	</div>
</x-layout.site>
