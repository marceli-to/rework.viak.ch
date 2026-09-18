<script setup>
import { computed } from 'vue';

const props = defineProps({ state: { type: String, required: true } });

const LABELS = {
	planned: 'Geplant',
	confirmed: 'Bestätigt',
	cancelled: 'Abgesagt',
	closed: 'Geschlossen',
};

/*
 * Only palette colours ([[00-foundation]]). Dropping the invented `teal-tint`
 * collapsed confirmed, planned and closed onto one grey, so confirmed takes the
 * brand colour solid instead — it is the state that matters, since it is the one
 * that raises invoices.
 */
const CLASSES = {
	planned: 'bg-gray-200 text-gray-600',
	confirmed: 'bg-teal text-white',
	cancelled: 'bg-danger/10 text-danger',
	closed: 'bg-gray-200 text-gray-400',
};

const label = computed(() => LABELS[props.state] ?? props.state);
const classes = computed(() => CLASSES[props.state] ?? CLASSES.planned);
</script>

<template>
	<span class="inline-block px-8 py-2 text-xxs font-semibold" :class="classes">{{ label }}</span>
</template>
