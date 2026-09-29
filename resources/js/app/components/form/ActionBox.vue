<script setup>
/**
 * Legacy's `danger-zone` in its other two tones: `.form-danger-zone.is-success`
 * and `.is-warning` (`form/_layout.scss`), the course-date form's *Bestätigen*
 * and *Absagen*. The frame is [[ResourceForm]]'s delete box, border and text
 * in the tone's colour.
 */
defineProps({
	tone: { type: String, default: 'success' },
});

const TONES = {
	success: 'border-success text-success',
	warning: 'border-warning text-warning',
};
</script>

<template>
	<div class="mt-24 border-2 p-8 text-md sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl" :class="TONES[tone] ?? TONES.success">
		<slot />
	</div>
</template>
