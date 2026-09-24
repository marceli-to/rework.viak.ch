<script setup>
import { useId } from 'vue';

/**
 * `resources/views/components/form/checkbox.blade.php` — keep the classes in
 * step. A 12px black square, 14 from sm, **solid teal when checked**, no tick;
 * the label 8px away, 12 from sm, and the box centred on the label's first line
 * by an offset rather than by `items-center`.
 *
 * `v-model` takes a boolean, or an array with `value` — the taxonomy lists.
 */
const model = defineModel({ type: [Boolean, Array], default: false });

defineProps({ value: { type: [String, Number], default: null } });

const id = useId();
</script>

<template>
	<div class="flex items-start">
		<input
			:id="id"
			v-model="model"
			type="checkbox"
			:value="value"
			class="mt-3 size-12 shrink-0 appearance-none border border-black bg-white outline-hidden checked:border-teal checked:bg-teal focus-visible:outline-1 focus-visible:outline-offset-2 focus-visible:outline-teal sm:mt-3 sm:size-14 lg:mt-5"
		/>
		<label :for="id" class="ml-8 cursor-pointer text-md leading-[1.3] sm:ml-12 sm:text-lg lg:text-xl"><slot /></label>
	</div>
</template>
