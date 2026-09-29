<script setup>
import { useId } from 'vue';

/**
 * `resources/views/components/form/field.blade.php` — keep the classes in step.
 *
 * A black label (14/16/18) over a **bold teal value on a single black rule**
 * (16/18/24), 16px to the next field and 32 from `lg`; the error under it in
 * red. `leading-[normal]`, as legacy's normalize sets on inputs.
 */
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
	label: { type: String, default: null },
	type: { type: String, default: 'text' },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
	// Shown, not typed: a generated discount code. The value on its rule, in black.
	readonly: { type: Boolean, default: false },
	// A line under the field — *Leer lassen für unbegrenzt.*
	hint: { type: String, default: null },
});

const id = useId();

// A password field is never filled in by the browser (`new-password`): a
// remembered password in *Neues Passwort* would change it on the next save.
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<label v-if="label" :for="id" class="mb-4 block text-md sm:text-lg lg:text-xl">
			{{ label }}<template v-if="required"> *</template>
		</label>
		<input
			:id="id"
			v-model="model"
			:type="type"
			:required="required"
			:readonly="readonly"
			:autocomplete="type === 'password' ? 'new-password' : null"
			:aria-invalid="error ? 'true' : null"
			class="block w-full bg-transparent py-4 text-lg leading-[normal] font-bold outline-hidden sm:text-xl lg:text-3xl"
			:class="[readonly ? 'text-black' : 'text-teal', error ? 'border-b border-danger' : 'border-b border-black']"
		/>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
		<div v-else-if="hint" class="pt-8 text-md text-gray-600 lg:text-lg">{{ hint }}</div>
	</div>
</template>
