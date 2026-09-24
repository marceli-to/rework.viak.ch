<script setup>
import EventState from './EventState.vue';
import Button from '@/components/ui/Button.vue';
import { longDate } from '@/support/format';

/**
 * One course date inside a course on *Kurse* — legacy's `StackedListEvent.vue`
 * with `dashboard: true`, which is `resources/views/components/row/event.blade.php`
 * with a different middle and right column: the same 1px black rule, 32px above
 * and 16px inside, three `span-4` columns.
 *
 * Every day with its hours on the left; the place, the expert and the state in
 * the middle; the seats and laptops on the right, with *Bearbeiten* and
 * *Details* stacked against the far edge, 12px apart.
 */
defineProps({ event: { type: Object, required: true } });
</script>

<template>
	<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="sm:col-span-4">
				<template v-for="(date, index) in event.dates" :key="date.date">
					<strong class="font-bold">{{ longDate(date.date) }}</strong><br />
					{{ date.time_start }} – {{ date.time_end }} Uhr<br v-if="index < event.dates.length - 1" />
				</template>
			</div>

			<div class="sm:col-span-4">
				<div v-if="event.online">Onlinekurs</div>
				<div v-else>
					<a v-if="event.map" :href="event.map" target="_blank" rel="noopener" title="Karte öffnen" class="hover:text-teal">{{ event.location }}</a>
					<template v-else>{{ event.location }}</template>
				</div>
				<div v-if="event.experts.length">mit {{ event.experts.join(', ') }}</div>
				<EventState :state="event.state" />
			</div>

			<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
				<div>
					<div :class="{ 'text-success': event.bookings >= event.max_participants }">
						{{ event.bookings }}&thinsp;/&thinsp;{{ event.max_participants }} Teilnehmer
					</div>
					<div v-if="event.rentals_available">{{ event.rentals }}&thinsp;/&thinsp;{{ event.rentals_available }} Mietcomputer</div>
				</div>
				<div class="mt-24 sm:mt-0">
					<Button :to="{ name: 'event.edit', params: { uuid: event.uuid } }" class="mb-12">Bearbeiten</Button>
					<Button :to="{ name: 'event.show', params: { uuid: event.uuid } }" variant="secondary">Details</Button>
				</div>
			</div>
		</div>
	</article>
</template>
