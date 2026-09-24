<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

/**
 * `resources/views/components/ui/button.blade.php`, the same classes — keep the
 * two in step ([[viak-dashboard-looks-like-the-site]]).
 *
 * 14px, 16px from sm, bold, 28px high, 140px wide from sm; teal primary and
 * grey secondary, both black on hover. `to` renders a router link, `href` a
 * plain one, neither a `<button>`.
 */
const props = defineProps({
	variant: { type: String, default: 'primary' },
	to: { type: [String, Object], default: null },
	href: { type: String, default: null },
	type: { type: String, default: 'button' },
});

const STYLES = {
	primary: 'bg-teal text-white hover:bg-black',
	secondary: 'bg-gray-400 text-white hover:bg-black',
	outline: 'border border-teal bg-white font-normal text-teal hover:border-black hover:text-black',
	danger: 'bg-danger text-white hover:bg-danger-dark',
	gray: 'border border-gray-600 bg-gray-600 text-white',
	'gray-outline': 'border border-gray-600 bg-white text-gray-600',
};

const classes = computed(() => [
	'flex min-h-28 items-center justify-center px-24 text-md font-bold transition-colors max-sm:w-full sm:min-w-140 sm:text-lg',
	STYLES[props.variant] ?? STYLES.primary,
]);
</script>

<template>
	<RouterLink v-if="to" :to="to" :class="classes"><slot /></RouterLink>
	<a v-else-if="href" :href="href" :class="classes"><slot /></a>
	<button v-else :type="type" :class="classes"><slot /></button>
</template>
