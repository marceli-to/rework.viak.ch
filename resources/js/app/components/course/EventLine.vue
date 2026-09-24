<script setup>
import { RouterLink } from 'vue-router';
import EventState from './EventState.vue';
import IconEdit from '@/components/icons/Edit.vue';
import IconArrowRight from '@/components/icons/ArrowRight.vue';
import { shortDate } from '@/support/format';

/**
 * One course date in *Kurse*'s chronological mode — legacy's
 * `stacked-list-item--course-events`, measured on its dashboard on 2026-09-24.
 *
 * A `#505050` rule, 2px, 32px above and 16px inside; 18px at a line height of
 * 1.4. Three columns of a 12-column grid, **2 / 6 / 4** rather than the portal
 * row's three fours: the date and hours; the course number and title in bold
 * with the place, the experts and the state under it; the seats and laptops.
 * The pencil sits 14px below the rule on the right edge, the arrow's point 49px
 * below it. The first row is 12px under the switcher, the rest 32px apart.
 *
 * A full course — every seat taken — reads green, as legacy marks it.
 */
defineProps({
	event: { type: Object, required: true },
	course: { type: Object, required: true },
});
</script>

<template>
	<article class="relative mt-16 border-t border-gray-600 pt-8 leading-[1.5] first-of-type:mt-12 sm:mt-32 sm:first-of-type:mt-12 sm:border-t-2 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
		<RouterLink :to="{ name: 'event.edit', params: { uuid: event.uuid } }" title="Bearbeiten" class="absolute top-12 right-0 z-10 block size-18 hover:text-teal">
			<IconEdit class="block" />
		</RouterLink>
		<RouterLink :to="{ name: 'event.show', params: { uuid: event.uuid } }" title="Details" class="absolute top-47 right-0 block w-20 hover:text-teal">
			<IconArrowRight size="sm" class="[&_svg]:w-20" />
		</RouterLink>

		<div class="max-sm:pr-40 sm:grid sm:grid-cols-12 sm:gap-x-16 lg:gap-x-40">
			<div class="sm:col-span-2">
				<strong class="font-bold">{{ shortDate(event.dates[0]?.date ?? event.date) }}</strong>
				<template v-if="event.dates[0]?.time_start">
					<br />{{ event.dates[0].time_start }} – {{ event.dates[0].time_end }} Uhr
				</template>
			</div>

			<div class="sm:col-span-6">
				<h2 class="font-bold"><em class="not-italic">{{ course.number }}</em><span class="ml-8">{{ course.title }}</span></h2>
				<template v-if="event.online">Onlinekurs</template>
				<a v-else-if="event.map" :href="event.map" target="_blank" rel="noopener" title="Karte öffnen" class="hover:text-teal">{{ event.location }}</a>
				<span v-else>{{ event.location }}</span>
				<template v-if="event.experts.length"> ({{ event.experts.join(', ') }})</template>
				<EventState :state="event.state" />
			</div>

			<div class="sm:col-span-4">
				<div :class="{ 'text-success': event.bookings >= event.max_participants }">
					{{ event.bookings }}&thinsp;/&thinsp;{{ event.max_participants }} Teilnehmer
				</div>
				<div v-if="event.rentals_available">{{ event.rentals }}&thinsp;/&thinsp;{{ event.rentals_available }} Mietcomputer</div>
			</div>
		</div>
	</article>
</template>
