<script setup>
import { nextTick, ref, watch } from 'vue';
import Button from './Button.vue';
import Overlay from './Overlay.vue';
import { answer, confirmState as state } from '@/composables/useConfirm';

/**
 * `resources/views/components/ui/confirm-dialog.blade.php` on
 * `resources/views/components/ui/modal.blade.php`. The veil, and how it
 * closes, is `Overlay`, shared with the lightbox.
 *
 * A white veil at 90 %, a 600px box from sm with a `#505050` border, the
 * question bold and centred, then *Bestätigen* and *Abbrechen* stacked, 240px
 * wide, 12px apart. Escape or a click on the veil answers no.
 *
 * Two departures from the Blade dialog, which keeps its own:
 * - **The border is 2px, not 3**, the lightbox's (Marcel, 2026-09-24).
 * - **The text keeps its line breaks**: the testimonial form asks with the
 *   quote and its author on lines of their own.
 *
 * *Abbrechen* takes the focus when it opens, so an Enter pressed out of habit
 * does not delete anything.
 */
const cancel = ref(null);

watch(() => state.open, async (open) => {
	if (!open) return;
	await nextTick();
	cancel.value?.$el?.focus();
});
</script>

<template>
	<Overlay v-if="state.open" class="z-[1001] text-lg text-gray-600 lg:text-2xl" role="alertdialog" :aria-label="state.message" @close="answer(false)">
		<div class="flex max-w-[80%] cursor-default flex-col items-center border-2 border-gray-600 bg-white px-16 py-24 sm:w-600 sm:max-w-none lg:px-24 lg:py-32">
			<div class="text-center font-bold">{{ state.message }}</div>
			<div v-if="state.text" class="mt-16 text-center text-lg whitespace-pre-line">{{ state.text }}</div>

			<div class="mt-32 flex w-full flex-col items-center [&>*]:w-full [&>*]:max-w-240 [&>*+*]:mt-12">
				<Button variant="gray" @click="answer(true)">Bestätigen</Button>
				<Button ref="cancel" variant="gray-outline" @click="answer(false)">Abbrechen</Button>
			</div>
		</div>
	</Overlay>
</template>
