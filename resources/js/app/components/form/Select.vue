<script setup>
import { useId } from 'vue';

/**
 * `resources/views/components/form/select.blade.php` — keep the classes in
 * step. Bold teal on a black rule, legacy's grey triangle on the right
 * (`.select-chevron` in `app.css`). `options` is a list of `{ value, label }`,
 * or of groups — `{ label, options }` — drawn under their heading, as
 * *Bezieht sich auf* lists courses and software. `placeholder` is the empty
 * choice, first. The `action` slot sits at the right of the label: the
 * licence form's `+` that adds a choice ([[TermDialog]]).
 */
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
	label: { type: String, default: null },
	options: { type: Array, required: true },
	placeholder: { type: String, default: null },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
});

const id = useId();
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<div v-if="label" class="mb-4 flex items-center justify-between">
			<label :for="id" class="block text-md sm:text-lg lg:text-xl">{{ label }}<template v-if="required"> *</template></label>
			<slot name="action" />
		</div>
		<div class="select-chevron relative flex w-full items-center border-b py-8" :class="error ? 'border-danger' : 'border-black'">
			<select
				:id="id"
				v-model="model"
				class="block w-full cursor-pointer appearance-none bg-transparent pr-16 text-md font-bold text-teal outline-hidden sm:text-lg lg:text-xl"
			>
				<option v-if="placeholder" value="">{{ placeholder }}</option>
				<template v-for="option in options" :key="option.value ?? option.label">
					<optgroup v-if="option.options" :label="option.label">
						<option v-for="choice in option.options" :key="choice.value" :value="choice.value">{{ choice.label }}</option>
					</optgroup>
					<option v-else :value="option.value">{{ option.label }}</option>
				</template>
			</select>
		</div>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</div>
</template>
