<script setup>
import { bar } from '@/composables/useProgress';

/**
 * Legacy's `#nprogress` (`viak.ch/resources/sass/components/_progress.scss`):
 * a 2px teal bar fixed to the top of the window, clicks passing through, and
 * the *peg*, a 100px glow at its leading end tipped 3° and lifted 4px. The
 * spinner legacy switched off (`display: none`) is left out.
 */
</script>

<template>
	<div
		v-if="bar.value !== null"
		class="pointer-events-none fixed inset-x-0 top-0 z-[2000] h-2 transition-opacity duration-200 ease-linear"
		:class="{ 'opacity-0': bar.fading }"
	>
		<div class="relative h-full bg-teal transition-transform duration-200 ease-[ease]" :style="{ transform: `translateX(${(bar.value - 1) * 100}%)` }">
			<div class="absolute right-0 h-full w-100 -translate-y-4 rotate-3 shadow-[0_0_10px_var(--color-teal),0_0_5px_var(--color-teal)]" />
		</div>
	</div>
</template>
