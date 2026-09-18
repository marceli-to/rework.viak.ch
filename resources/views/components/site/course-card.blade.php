@props(['course'])
@php($next = $course->events->first())
<a href="{{ \App\Support\SiteUrl::course($course->getTranslation('slug', app()->getLocale())) }}"
   class="group flex flex-col border border-line bg-white transition hover:border-teal">
	{{-- The ported image where there is one; 306 came across with their crops.
	     The initial is the fallback, not the design ([[08-accounts]]). --}}
	@if ($image = $course->teaser())
		<x-media.image
			:media="$image"
			sizes="(min-width: 1132px) 360px, (min-width: 700px) 50vw, 100vw"
			:max-width="768"
			class="aspect-16/9 w-full object-cover"
		/>
	@else
		<div class="flex aspect-16/9 items-center justify-center bg-teal-tint text-3xl text-teal">
			{{ mb_substr($course->getTranslation('title', app()->getLocale()), 0, 1) }}
		</div>
	@endif

	<div class="flex flex-1 flex-col gap-2 p-5">
		<div class="flex flex-wrap gap-1.5">
			@foreach ($course->software as $software)
				<span class="bg-paper px-2 py-0.5 text-[11px] font-medium text-muted">
					{{ $software->getTranslation('title', app()->getLocale()) }}
				</span>
			@endforeach
		</div>

		<h2 class="text-base font-semibold leading-snug text-ink group-hover:text-teal-dark">
			{{ $course->getTranslation('title', app()->getLocale()) }}
		</h2>

		@if ($subtitle = $course->getTranslation('subtitle', app()->getLocale(), false))
			<p class="text-xs leading-relaxed text-faint">{{ $subtitle }}</p>
		@endif

		<div class="mt-auto flex items-end justify-between pt-3">
			<span class="text-xs text-muted">
				@if ($next)
					ab {{ $next->date->format('d.m.Y') }}
				@else
					Termine auf Anfrage
				@endif
			</span>
			<span class="text-sm font-semibold text-ink">CHF {{ number_format((float) $course->fee, 0, '.', "'") }}.–</span>
		</div>
	</div>
</a>
