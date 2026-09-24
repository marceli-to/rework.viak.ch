<script setup>
import IconCross from '@/components/icons/Cross.vue';
import Overlay from './Overlay.vue';

/**
 * Started from `resources/views/components/ui/lightbox.blade.php`: a white veil
 * at 90 %, a box with a 2px `#505050` border, 600 to 900px wide from sm, 24px
 * padding, a bold teal title and 24px down to the body. The veil, and how it
 * closes, is `Overlay`.
 *
 * **One departure, on purpose:** the close cross is always fixed 32px from the
 * window's top and right edges, outside the box. Legacy does that only on its
 * cropper and puts the cross beside the title everywhere else; Marcel wanted
 * the two to look the same (2026-09-24), and the dashboard is not held to
 * parity. `wide` is the cropper's fixed 900px box.
 */
defineProps({
	title: { type: String, default: null },
	wide: { type: Boolean, default: false },
	closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);
</script>

<template>
	<Overlay
		class="z-[200] text-lg leading-[1.3] sm:text-xl lg:text-3xl"
		role="dialog"
		:aria-label="title"
		:close-on-backdrop="closeOnBackdrop"
		@close="emit('close')"
	>
		<button type="button" class="fixed top-32 right-32 transition-colors hover:text-teal" aria-label="Schliessen" @click="emit('close')">
			<IconCross />
		</button>

		<div class="relative max-w-[90%] cursor-default border-2 border-gray-600 bg-white p-12 sm:p-24" :class="wide ? 'w-[90%] sm:w-900' : 'sm:max-w-900 sm:min-w-600'">
			<div class="max-h-[90vh] overflow-y-auto px-4">
				<h1 v-if="title" class="mb-12 font-bold text-teal">{{ title }}</h1>
				<div :class="{ 'mt-24': title }"><slot /></div>
			</div>
		</div>
	</Overlay>
</template>
