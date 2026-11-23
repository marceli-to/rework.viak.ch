{{--
	The homepage's top (Oliver, Roxy 2026-11-12, [[04-content]] *The homepage
	top*): legacy's teal intro and its slider gave way to one picture with the
	page's opening over it, as his screen print shows.

	- **The picture is the video's placeholder.** A video is to be recorded;
	  until then the first of the home hero images (*Seiteninhalte →
	  Startseite*) stands in, cropped to fill. Darkened so white type reads on
	  any image.
	- **Over it**: the headline, *Kurse* and *Software* in it linking to their
	  lists; *Was möchtest du machen?* small; the Vorhaben as small white boxes,
	  three across from lg, two below (Oliver: "dürfen viel kleiner sein");
	  then *Alle Kurse und Software von A bis Z* to *Alle Angebote*.
	- **No icons in the boxes** (Marcel, 2026-11-23): Oliver's screendesign has
	  them; they add nothing a title does not say.
	- The boxes keep the old Vorhaben tiles' look (the course card's frame):
	  white here, the title teal, the line under it grey, filling teal on
	  hover. Which Vorhaben and their lines are VIAK's, in the dashboard.
--}}
@php($hero = $slides->first())

<section @class(['relative overflow-hidden', 'bg-black' => ! $hero])>
	@if ($hero)
		<x-media.image :media="$hero" ratio="16/9" sizes="(min-width: 1200px) 1168px, 100vw" :max-width="1600" alt="" loading="eager" class="absolute inset-0 size-full object-cover" />
		<div class="absolute inset-0 bg-black/55" aria-hidden="true"></div>
	@endif

	<div class="relative px-16 py-40 text-white sm:px-40 sm:py-64 lg:px-80 lg:py-80">
		<h1 class="text-center text-2xl leading-[1.2] font-bold text-balance sm:text-3xl lg:text-4xl">
			<a href="{{ \App\Support\SiteUrl::courses() }}" class="underline decoration-2 underline-offset-[0.15em] hover:text-teal">Kurse</a>
			und
			<a href="{{ \App\Support\SiteUrl::softwareIndex() }}" class="underline decoration-2 underline-offset-[0.15em] hover:text-teal">Software</a>
			für digitales Gestalten
		</h1>

		@if ($projects->isNotEmpty())
			<h2 class="mt-32 mb-12 text-md font-bold sm:mt-48 lg:mb-16 lg:text-lg">Was möchtest du machen?</h2>

			<div class="grid grid-cols-2 gap-8 sm:gap-16 lg:grid-cols-3">
				@foreach ($projects as $project)
					<a
						href="{{ \App\Support\SiteUrl::project($project->getTranslation('slug', app()->getLocale())) }}"
						class="group block bg-white p-8 text-black transition-colors duration-[120ms] ease-in-out hover:bg-teal hover:text-white sm:p-16"
					>
						<h3 class="text-lg leading-[1.2] break-words hyphens-auto text-teal group-hover:text-white lg:text-2xl">
							{{ $project->getTranslation('title', app()->getLocale()) }}
						</h3>

						@if ($teaser = $project->getTranslation('teaser', app()->getLocale(), false))
							<p class="mt-4 text-xxs leading-[1.3] font-medium text-gray-600 group-hover:text-white sm:text-sm">{{ $teaser }}</p>
						@endif
					</a>
				@endforeach
			</div>
		@endif

		<p class="mt-24 text-center text-md font-bold sm:mt-32 lg:text-lg">
			<a href="{{ \App\Support\SiteUrl::offers() }}" class="underline decoration-1 underline-offset-4 hover:text-teal">Alle Kurse und Software von A bis Z</a>
		</p>
	</div>
</section>
