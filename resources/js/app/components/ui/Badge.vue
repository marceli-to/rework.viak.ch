<script setup>
/**
 * A state, set off from the text around it: *Kurs findet statt*, *nicht
 * publiziert* ([[07-dashboard]]). Dashboard only. The site keeps its italic
 * state line (`components/course/event-state.blade.php`).
 *
 * The outline button's shape (`Button.vue`, `outline`): a 1px border, square
 * corners, bold text in the border's colour. Not filled: white on `warning`
 * or `success` is too faint to read at 14px. `neutral` is a state that is
 * neither good nor bad.
 */
defineProps({
	variant: { type: String, default: 'neutral' },
});

const TONES = {
	neutral: 'border-black text-black',
	success: 'border-success text-success',
	warning: 'border-warning text-warning',
	danger: 'border-danger text-danger',
};
</script>

<template>
	<span class="inline-block border px-8 py-2 text-md leading-[1.2] font-bold" :class="TONES[variant] ?? TONES.neutral"><slot /></span>
</template>
