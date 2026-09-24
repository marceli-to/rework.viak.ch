<script setup>
import { ref, useId } from 'vue';

/**
 * The drop box of `resources/views/components/form/file-input.blade.php` —
 * keep the classes in step. 150px high, a 1px black line like the editor's
 * (Marcel, 2026-09-23), teal while a file is dragged over it or it has the
 * focus, and the restrictions in small grey capitals under it.
 *
 * It only hands the files over (`@files`); what happens to them is the
 * caller's, which on the course form is an upload each.
 */
defineProps({
	accept: { type: String, default: null },
	restrictions: { type: String, default: null },
	multiple: { type: Boolean, default: true },
});

const emit = defineEmits(['files']);

const id = useId();
const dragging = ref(false);

function take(list) {
	const files = [...(list ?? [])];
	if (files.length) emit('files', files);
}

function dropped(event) {
	dragging.value = false;
	take(event.dataTransfer?.files);
}

function picked(event) {
	take(event.target.files);
	event.target.value = '';
}
</script>

<template>
	<div>
		<label
			:for="id"
			:data-dragging="dragging ? '' : null"
			class="flex min-h-150 cursor-pointer items-center justify-center border border-black p-20 text-center text-md transition-colors hover:border-teal has-[:focus-visible]:border-teal data-dragging:border-teal sm:text-lg lg:text-xl"
			@dragover.prevent="dragging = true"
			@dragleave.prevent="dragging = false"
			@drop.prevent="dropped"
		>
			<span class="pointer-events-none">Dateien hierher ziehen oder klicken</span>
			<input :id="id" type="file" :accept="accept" :multiple="multiple" class="sr-only" @change="picked" />
		</label>
		<span v-if="restrictions" class="mt-4 inline-block text-xs text-gray-400 uppercase lg:text-md">{{ restrictions }}</span>
	</div>
</template>
