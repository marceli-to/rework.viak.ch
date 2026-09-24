<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';

/**
 * Whether a course date is happening, as a badge. The site says it in an
 * italic line (`resources/views/components/course/event-state.blade.php`),
 * which the dashboard drew the same way until 2026-09-24.
 *
 * The dashboard's labels are legacy's `EventState.vue` with `dashboard: true`,
 * which differ from the site's only for the two end states.
 */
const props = defineProps({ state: { type: String, required: true } });

const STATES = {
	closed: ['Kurs abgeschlossen', 'neutral'],
	cancelled: ['Kurs abgesagt', 'danger'],
	confirmed: ['Kurs findet statt', 'success'],
	planned: ['Kurs offen, wird bestätigt', 'warning'],
};

const state = computed(() => STATES[props.state] ?? STATES.planned);
</script>

<template>
	<div class="mt-4"><Badge :variant="state[1]">{{ state[0] }}</Badge></div>
</template>
