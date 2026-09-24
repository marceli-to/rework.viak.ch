<script setup>
import { computed } from 'vue';

/**
 * `resources/views/components/course/event-state.blade.php` — the italic line
 * that says whether a course date is happening, in the row's own size.
 *
 * The dashboard's labels are legacy's `EventState.vue` with `dashboard: true`,
 * which differ from the site's only for the two end states.
 */
const props = defineProps({ state: { type: String, required: true } });

const STATES = {
	closed: ['Kurs abgeschlossen', ''],
	cancelled: ['Kurs abgesagt', 'text-danger'],
	confirmed: ['Kurs findet statt', 'text-success'],
	planned: ['Kurs offen, wird bestätigt', 'text-warning'],
};

const state = computed(() => STATES[props.state] ?? STATES.planned);
</script>

<template>
	<div :class="state[1]"><em class="italic">{{ state[0] }}</em></div>
</template>
