<script setup>
import EventState from './EventState.vue';
import Badge from '@/components/ui/Badge.vue';
import { courseNumber, longDate } from '@/support/format';

/**
 * One seat on a student's page — legacy's `StackedListEvent.vue` with a
 * `booking`, drawn on [[EventRow]]'s geometry: the same rule and three
 * `span-4` columns. The course and its days on the left, where the date row
 * has only the days; the place, the expert and the state in the middle; what
 * is particular to this seat on the right, with the actions against the far
 * edge. The `badges` slot sits beside the state badge. **No *Details***
 * (Marcel, 2026-09-29): the student page is about the student.
 */
defineProps({ booking: { type: Object, required: true } });
</script>

<template>
	<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="sm:col-span-4">
				<strong class="font-bold">{{ courseNumber(booking.course.number) }} {{ booking.course.title }}</strong><br />
				<template v-for="(date, index) in booking.event.dates" :key="date.date">
					{{ longDate(date.date) }}, {{ date.time_start }} – {{ date.time_end }} Uhr<br v-if="index < booking.event.dates.length - 1" />
				</template>
			</div>

			<div class="sm:col-span-4">
				<div v-if="booking.event.online">Onlinekurs</div>
				<div v-else>{{ booking.event.location }}</div>
				<div v-if="booking.event.experts.length">mit {{ booking.event.experts.join(', ') }}</div>
				<!-- The seat's own badges beside the date's state, on one line (Marcel, 2026-09-29):
				     all of them children of one row, 8px apart across and down, so a wrap spaces them the same wherever
				     it falls, and `items-start` keeps each at its own height. -->
				<div class="mt-4 flex flex-wrap items-start gap-8">
					<EventState :state="booking.event.state" class="mt-0! flex" />
					<!-- A seat on an event legacy deleted: kept as history, not opened ([[CustomerPageController]]). -->
					<Badge v-if="booking.deleted" variant="danger">Veranstaltung gelöscht</Badge>
					<slot name="badges" />
				</div>
			</div>

			<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
				<div><slot name="facts" /></div>
				<div class="mt-24 sm:mt-0 [&>*+*]:mt-12">
					<slot name="actions" />
				</div>
			</div>
		</div>
	</article>
</template>
