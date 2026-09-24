<script setup>
import { useId } from 'vue';

/**
 * `resources/views/components/form/select.blade.php` — keep the classes in
 * step. Bold teal on a black rule, legacy's grey triangle on the right
 * (`.select-chevron` in `app.css`). `options` is a list of `{ value, label }`.
 */
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
	label: { type: String, default: null },
	options: { type: Array, required: true },
	error: { type: String, default: null },
});

const id = useId();
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<label v-if="label" :for="id" class="mb-4 block text-md sm:text-lg lg:text-xl">{{ label }}</label>
		<div class="select-chevron relative flex w-full items-center border-b py-8" :class="error ? 'border-danger' : 'border-black'">
			<select
				:id="id"
				v-model="model"
				class="block w-full cursor-pointer appearance-none bg-transparent pr-16 text-md font-bold text-teal outline-hidden sm:text-lg lg:text-xl"
			>
				<option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
			</select>
		</div>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</div>
</template>
