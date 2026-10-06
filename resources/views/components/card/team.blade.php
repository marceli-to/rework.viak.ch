@props(['member'])

@php
	$portrait = $member->media->firstWhere('is_teaser', true) ?? $member->media->first();
	$role = $member->getTranslation('role', app()->getLocale(), false);
@endphp

{{--
	One person on the team, on *Über uns* ([[04-content]]): the expert card's
	frame and type ([[card.expert]]), so the two grids read as one page. What
	differs is what a team member has: **no page to link to**, so no hover
	overlay, and one line of what they do under the name, where the mockup puts
	it.
--}}
<article {{ $attributes->class(['border border-teal p-8 lg:p-16']) }}>
	<header class="min-h-70 sm:min-h-90 lg:min-h-130">
		<h2 class="text-lg leading-[1.2] break-words hyphens-auto text-teal sm:text-2xl lg:text-4xl">{{ $member->name }}</h2>
		@if ($role)
			<p class="mt-4 text-md sm:text-lg lg:text-xl">{{ $role }}</p>
		@endif
	</header>

	<figure class="mt-8 block">
		@if ($portrait)
			<x-media.image
				:media="$portrait"
				ratio="1/1"
				sizes="(min-width: 1132px) 330px, (min-width: 700px) 30vw, 50vw"
				:max-width="1024"
				:alt="$member->name"
				class="block w-full"
			/>
		@else
			<div class="aspect-square w-full bg-gray-200"></div>
		@endif
	</figure>
</article>
