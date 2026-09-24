<script>
// The open overlays, newest last: Escape closes only the top one, so a
// confirm asked over a lightbox does not take the lightbox with it.
const stack = [];
</script>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * What the lightbox and the confirm dialog share: the white veil at 90 %
 * over the page, and how it closes. Each draws its own box inside, as legacy's
 * `lightbox.blade.php` and `modal.blade.php` do.
 *
 * Escape is listened for on the document, not the veil: after a click on the
 * pencil the focus is still on it, behind the veil, so a key handler on the
 * veil never hears anything. `closeOnBackdrop` off keeps a veil click from
 * closing: the cropper's, where a lost crop costs more than a stray click.
 *
 * Classes and attributes go on the veil, where the caller sets its layer,
 * its type size and its role.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps({
	closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const self = {};

function onKeydown(event) {
	if (event.key === 'Escape' && stack.at(-1) === self) emit('close');
}

onMounted(() => {
	stack.push(self);
	document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
	stack.splice(stack.indexOf(self), 1);
	document.removeEventListener('keydown', onKeydown);
});

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
			v-bind="$attrs"
			class="fixed inset-0 flex items-center justify-center overflow-y-auto bg-white/90"
			aria-modal="true"
			:class="{ 'cursor-pointer': closeOnBackdrop }"
			@mousedown="onVeilPress"
			@click="onVeilClick"
		>
			<slot />
		</div>
	</Teleport>
</template>
