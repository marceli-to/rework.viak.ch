<script setup>
import Badge from '@/components/ui/Badge.vue';

/**
 * Whether a seat attended, always shown (Marcel, 2026-09-29): *Teilnahme
 * offen* until the event is closed, then *Teilgenommen* or *Nicht
 * teilgenommen*, as recorded when it was closed ([[EventPageController::close]]).
 * On the event's page and on the student's.
 */
defineProps({
	participated: { type: Boolean, default: false },
	closed: { type: Boolean, default: false },
});
</script>

<template>
	<Badge v-if="!closed">Teilnahme offen</Badge>
	<Badge v-else-if="participated" variant="success">Teilgenommen</Badge>
	<Badge v-else variant="danger">Nicht teilgenommen</Badge>
</template>
