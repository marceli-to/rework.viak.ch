<script setup>
import { nextTick, onMounted, ref, useId, watch } from 'vue';

/**
 * Legacy's dashboard textarea — `textarea.is-small.has-autosize` — measured on
 * its course form on 2026-09-24. The field's rule and teal, but **18px** at a
 * line height of 1.3 rather than the input's 24, and it **grows with its
 * text** instead of scrolling. The site has no twin: it is the subtitle's and
 * the SEO fields' box, and nothing public writes into one.
 */
const model = defineModel({ type: String, default: '' });

defineProps({
	label: { type: String, default: null },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
	rows: { type: Number, default: 2 },
	mono: { type: Boolean, default: false },
});

const id = useId();
const box = ref(null);

async function grow() {
	await nextTick();
	if (!box.value) return;
	box.value.style.height = 'auto';
	box.value.style.height = `${box.value.scrollHeight + 1}px`;
}

watch(model, grow);
onMounted(grow);
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<label v-if="label" :for="id" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ label }}<template v-if="required"> *</template>
		</label>
		<textarea
			:id="id"
			ref="box"
			v-model="model"
			:rows="rows"
			:required="required"
			:aria-invalid="error ? 'true' : null"
			class="block w-full resize-none overflow-hidden border-b bg-transparent py-4 text-md leading-[1.3] font-bold text-teal outline-hidden sm:text-lg lg:text-xl"
			:class="[error ? 'border-danger' : 'border-black', mono ? 'font-mono font-normal' : '']"
		/>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</div>
</template>
