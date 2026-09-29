<script setup>
/**
 * A state, set off from the text around it: *Kurs findet statt*, *nicht
 * publiziert* ([[07-dashboard]]). Dashboard only. The site keeps its italic
 * state line (`components/course/event-state.blade.php`).
 *
 * The outline button's shape (`Button.vue`, `outline`): a 1px border and bold
 * text in the border's colour, **square like everything else** (Marcel,
 * 2026-09-29: `rounded-sm` was tried and dropped). Badges about one row sit
 * together on one line; one that qualifies a value sits right after it. Not filled:
 * white on `warning` or `success` is too faint to read at 14px. `neutral` is
 * a state that is neither good nor bad. `solid` (`gray-600`, white text) is the
 * one filled tone: the total in a collapsible title (Marcel, 2026-09-29).
 */
defineProps({
	variant: { type: String, default: 'neutral' },
});

const TONES = {
	neutral: 'border-black text-black',
	solid: 'border-gray-600 bg-gray-600 text-white',
	success: 'border-success text-success',
	warning: 'border-warning text-warning',
	danger: 'border-danger text-danger',
};
</script>

<template>
	<span class="inline-block border px-4 py-1 text-md leading-[1.2] font-bold" :class="TONES[variant] ?? TONES.neutral"><slot /></span>
</template>
