<script setup>
import { inject, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

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

/**
 * Sized to its text. **Not while it is hidden**: inside a shut collapsible it
 * measures 0 and would be pinned at 1px, which is what happened under
 * *Metatags + SEO* (Marcel, 2026-09-24). So it measures again when the
 * collapsible around it opens — told directly, rather than waiting for a
 * `ResizeObserver`, which only reports on a painted frame — and the observer is
 * kept for what it is good at: the column's width changing the wrapping.
 */
async function grow() {
	await nextTick();
	const el = box.value;
	if (!el || el.offsetParent === null) return;
	el.style.height = 'auto';
	el.style.height = `${el.scrollHeight + 1}px`;
}

let observer = null;
let width = 0;

onMounted(() => {
	grow();
	observer = new ResizeObserver(([entry]) => {
		// Only a change of width can change the wrapping; reacting to height
		// would answer its own resize.
		if (entry.contentRect.width === width) return;
		width = entry.contentRect.width;
		grow();
	});
	observer.observe(box.value);
});

onBeforeUnmount(() => observer?.disconnect());
watch(model, grow);

const shown = inject('collapsibleOpen', null);
if (shown) watch(shown, (open) => open && grow());
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
