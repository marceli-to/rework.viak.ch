<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import IconCross from '@/components/icons/Cross.vue';

/**
 * Started from `resources/views/components/ui/lightbox.blade.php`: a white veil
 * at 90 %, a box with a 2px `#505050` border, 600 to 900px wide from sm, 24px
 * padding, a bold teal title and 24px down to the body.
 *
 * **One departure, on purpose:** the close cross is always fixed 32px from the
 * window's top and right edges, outside the box. Legacy does that only on its
 * cropper and puts the cross beside the title everywhere else; Marcel wanted
 * the two to look the same (2026-09-24), and the dashboard is not held to
 * parity. `wide` is the cropper's fixed 900px box.
 *
 * Escape is listened for on the document, not the veil: after a click on the
 * pencil the focus is still on it, behind the veil, so a key handler on the
 * veil never hears anything. `closeOnBackdrop` off keeps a veil click from
 * closing — the cropper's, where a lost crop costs more than a stray click.
 */
const props = defineProps({
	title: { type: String, default: null },
	wide: { type: Boolean, default: false },
	closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

function onKeydown(event) {
	if (event.key === 'Escape') emit('close');
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));

// A drag that starts in the box and is let go over the veil ends in a click
// on the veil. Only a press that also began there counts.
let pressedOnVeil = false;

function onVeilPress(event) {
	pressedOnVeil = event.target === event.currentTarget;
}

function onVeilClick(event) {
	if (props.closeOnBackdrop && pressedOnVeil && event.target === event.currentTarget) emit('close');
	pressedOnVeil = false;
}
</script>

<template>
	<Teleport to="body">
		<div
			class="fixed inset-0 z-[200] flex items-center justify-center overflow-y-auto bg-white/90 text-lg leading-[1.3] sm:text-xl lg:text-3xl"
			role="dialog"
			aria-modal="true"
			:aria-label="title"
			:class="{ 'cursor-pointer': closeOnBackdrop }"
			@mousedown="onVeilPress"
			@click="onVeilClick"
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
		</div>
	</Teleport>
</template>
