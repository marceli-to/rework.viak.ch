<script setup>
import { nextTick, ref, watch } from 'vue';
import Button from './Button.vue';
import { answer, confirmState as state } from '@/composables/useConfirm';

/**
 * `resources/views/components/ui/confirm-dialog.blade.php` on
 * `resources/views/components/ui/modal.blade.php` — keep the classes in step.
 *
 * A white veil at 90 %, a 600px box from sm with a 3px `#505050` border, the
 * question bold and centred, then *Bestätigen* and *Abbrechen* stacked, 240px
 * wide, 12px apart. Escape or a click on the veil answers no.
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
	<div
		v-if="state.open"
		class="fixed inset-0 z-[1001] flex cursor-pointer items-center justify-center overflow-y-auto bg-white/90 text-lg text-gray-600 lg:text-2xl"
		role="alertdialog"
		aria-modal="true"
		:aria-label="state.message"
		@click.self="answer(false)"
		@keydown.esc="answer(false)"
	>
		<div class="flex max-w-[80%] cursor-default flex-col items-center border-[3px] border-gray-600 bg-white px-16 py-24 sm:w-600 sm:max-w-none lg:px-24 lg:py-32">
			<div class="text-center font-bold">{{ state.message }}</div>
			<div v-if="state.text" class="mt-16 text-center text-lg">{{ state.text }}</div>

			<div class="mt-32 flex w-full flex-col items-center [&>*]:w-full [&>*]:max-w-240 [&>*+*]:mt-12">
				<Button variant="gray" @click="answer(true)">Bestätigen</Button>
				<Button ref="cancel" variant="gray-outline" @click="answer(false)">Abbrechen</Button>
			</div>
		</div>
	</div>
</template>
