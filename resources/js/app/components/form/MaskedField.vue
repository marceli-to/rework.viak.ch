<script setup>
import { computed, useId } from 'vue';

/**
 * A day or a time, typed as legacy's `vue-the-mask` fields had them —
 * `TT.MM.JJJJ` and `hh.mm`, the dots put in as you type — and held as the
 * server takes them, `Y-m-d` and `H:i`. Drawn as [[Field]] is.
 *
 * Only a whole value is converted. A half-typed one — `25.09.25` — is sent as
 * typed, so the server refuses it by name; legacy let two-digit years through
 * and lost 14 events from the site that way ([[EventSchema]]).
 */
const model = defineModel({ type: String, default: '' });

const props = defineProps({
	kind: { type: String, default: 'date' },
	label: { type: String, default: null },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
});

const id = useId();
const date = computed(() => props.kind === 'date');

/** Digits in, dots where legacy's mask puts them. */
function mask(digits) {
	const cuts = date.value ? [2, 4] : [2];
	let out = '';
	[...digits].forEach((digit, index) => {
		if (cuts.includes(index)) out += '.';
		out += digit;
	});
	return out;
}

const shown = computed(() => {
	const value = model.value ?? '';
	if (date.value && /^\d{4}-\d{2}-\d{2}$/.test(value)) return value.split('-').reverse().join('.');
	if (!date.value && /^\d{2}:\d{2}$/.test(value)) return value.replace(':', '.');
	return value;
});

function type(event) {
	const digits = event.target.value.replace(/\D/g, '').slice(0, date.value ? 8 : 4);
	const typed = mask(digits);
	event.target.value = typed;

	if (date.value && digits.length === 8) model.value = `${digits.slice(4)}-${digits.slice(2, 4)}-${digits.slice(0, 2)}`;
	else if (!date.value && digits.length === 4) model.value = `${digits.slice(0, 2)}:${digits.slice(2)}`;
	else model.value = typed;
}
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<label v-if="label" :for="id" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ label }}<template v-if="required"> *</template>
		</label>
		<input
			:id="id"
			:value="shown"
			type="text"
			inputmode="numeric"
			:placeholder="date ? 'TT.MM.JJJJ' : 'hh.mm'"
			:required="required"
			:aria-invalid="error ? 'true' : null"
			class="block w-full bg-transparent py-4 text-lg leading-[normal] font-bold text-teal outline-hidden placeholder:font-normal placeholder:text-gray-400 placeholder:italic sm:text-xl lg:text-3xl"
			:class="error ? 'border-b border-danger' : 'border-b border-black'"
			@input="type"
		/>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</div>
</template>
