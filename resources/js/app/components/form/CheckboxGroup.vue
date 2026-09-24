<script setup>
import Checkbox from './Checkbox.vue';

/**
 * A heading and a list of checkboxes under it, closed by a black rule — legacy's
 * `form-group-header` + `form-group.line-after` on its course form, measured
 * 2026-09-24: an 18px heading 16px above the first box, 12px between boxes,
 * then 24px, the rule, and 32px to the next group. `columns` puts the boxes in
 * two, as legacy does for the 21 tags.
 */
const model = defineModel({ type: Array, default: () => [] });

defineProps({
	label: { type: String, required: true },
	options: { type: Array, required: true },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
	columns: { type: [Boolean, Number], default: false },
});
</script>

<template>
	<fieldset class="mb-32 border-b border-black pb-24">
		<legend class="mb-16 float-left w-full sm:text-lg lg:text-xl" :class="{ 'text-danger': error }">
			{{ label }}<template v-if="required"> *</template>
		</legend>
		<div class="clear-both" :class="columns ? 'grid grid-cols-2 gap-x-16 lg:gap-x-40' : ''">
			<div v-for="option in options" :key="option.value" class="mb-12 last:mb-0" :class="{ 'last:mb-12': columns }">
				<Checkbox v-model="model" :value="option.value">{{ option.label }}</Checkbox>
			</div>
		</div>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</fieldset>
</template>
