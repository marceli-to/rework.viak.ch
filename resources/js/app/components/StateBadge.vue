<script setup>
import { computed } from 'vue';

const props = defineProps({ state: { type: String, required: true } });

const LABELS = {
	planned: 'Geplant',
	confirmed: 'Bestätigt',
	cancelled: 'Abgesagt',
	closed: 'Geschlossen',
};

const CLASSES = {
	planned: 'bg-paper text-muted',
	confirmed: 'bg-teal-tint text-teal-dark',
	cancelled: 'bg-danger/10 text-danger',
	closed: 'bg-light text-faint',
};

const label = computed(() => LABELS[props.state] ?? props.state);
const classes = computed(() => CLASSES[props.state] ?? CLASSES.planned);
</script>

<template>
	<span class="inline-block px-2 py-0.5 text-[11px] font-semibold" :class="classes">{{ label }}</span>
</template>
