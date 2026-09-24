<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import IconCross from '@/components/icons/Cross.vue';

/**
 * Legacy's `search-container` (`form/_search.scss`), measured on its dashboard
 * on 2026-09-24: a bare input with a grey rule under it, **bold teal** once
 * typed into, *Suchbegriff…* in grey italic until then, 16px and 20px from
 * `bp-md`, and a 12px cross to clear it. `leading-[normal]`, the browser's own
 * — Tailwind's `leading-normal` is 1.5 and made the box 6px taller.
 *
 * It asks a little after the typing stops rather than on every key.
 */
const props = defineProps({
	modelValue: { type: String, default: '' },
	placeholder: { type: String, default: 'Suchbegriff…' },
});

const emit = defineEmits(['update:modelValue']);

const value = ref(props.modelValue);
let timer = null;

watch(() => props.modelValue, (next) => {
	if (next !== value.value) value.value = next;
});

function input(event) {
	value.value = event.target.value;
	clearTimeout(timer);
	timer = setTimeout(() => emit('update:modelValue', value.value.trim()), 200);
}

function clear() {
	clearTimeout(timer);
	value.value = '';
	emit('update:modelValue', '');
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
	<div class="relative">
		<input
			type="text"
			:value="value"
			:placeholder="placeholder"
			:aria-label="placeholder"
			class="block w-full border-b border-gray-400 pt-4 pb-4 text-lg leading-[normal] font-bold text-teal placeholder:font-normal placeholder:text-gray-400 placeholder:italic focus:outline-none lg:text-2xl"
			@input="input"
			@keydown.esc.prevent="clear"
		/>
		<button v-if="value" type="button" class="absolute top-12 right-0 block size-12 hover:text-teal" aria-label="Suche leeren" @click="clear">
			<IconCross size="sm" />
		</button>
	</div>
</template>
