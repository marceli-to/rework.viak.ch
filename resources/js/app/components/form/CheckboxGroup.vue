<script setup>
import Checkbox from './Checkbox.vue';

/**
 * A heading and a list of checkboxes under it, closed by a black rule — legacy's
 * `form-group-header` + `form-group.line-after` on its course form, measured
 * 2026-09-24: an 18px heading 16px above the first box, 12px between boxes,
 * then 24px, the rule, and 32px to the next group. `columns` puts the boxes in
 * two, as legacy does for the 21 tags, or in four, as its expert form's roles
 * (`span-3` each).
 */
const model = defineModel({ type: Array, default: () => [] });

defineProps({
	label: { type: String, required: true },
	options: { type: Array, required: true },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
	// As a field's, in its grey, but **above** the boxes: a long list is read
	// after the sentence that says when to tick any (the product's *Hostsoftware*).
	hint: { type: String, default: null },
	// Inside a collapsible of the same name the legend would say it twice:
	// kept for a screen reader, hidden from the eye.
	hideLabel: { type: Boolean, default: false },
	columns: { type: [Boolean, Number], default: false },
	// The event form's *Experten*: legacy's `<h3><strong>`, bold where the
	// course form's headings are not.
	strong: { type: Boolean, default: false },
});
</script>

<template>
	<fieldset class="mb-32 border-b border-black pb-24">
		<legend class="mb-16 float-left w-full sm:text-lg lg:text-xl" :class="{ 'text-danger': error, 'font-bold': strong, 'sr-only': hideLabel }">
			{{ label }}<template v-if="required"> *</template>
		</legend>
		<p v-if="hint" class="clear-both mb-16 text-md text-gray-600 lg:text-lg">{{ hint }}</p>
		<div class="clear-both" :class="columns ? ['grid gap-x-16 lg:gap-x-40', columns === 4 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-2'] : ''">
			<div v-for="option in options" :key="option.value" class="mb-12 last:mb-0" :class="{ 'last:mb-12': columns }">
				<Checkbox v-model="model" :value="option.value">{{ option.label }}</Checkbox>
			</div>
		</div>
		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</fieldset>
</template>
