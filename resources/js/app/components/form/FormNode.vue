<script setup>
import { computed } from 'vue';
import { get, set } from '@/support/path';
import Checkbox from './Checkbox.vue';
import CheckboxGroup from './CheckboxGroup.vue';
import Editor from './Editor.vue';
import Field from './Field.vue';
import Select from './Select.vue';
import Textarea from './Textarea.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import ImageSection from '@/components/media/ImageSection.vue';

/**
 * One node of a schema, drawn with its twin component ([[Field]], step 5).
 *
 * `model` is the object the node's `name` is read from — the whole form, or a
 * repeater's row — and `path` is where that object sits in the form, so an
 * error comes back to the field that caused it: `videos.1.code`.
 *
 * Custom parts are named in the schema and resolved here; the only one is the
 * course's image section, which is given the record it belongs to.
 */
defineOptions({ name: 'FormNode' });

const props = defineProps({
	field: { type: Object, required: true },
	model: { type: Object, required: true },
	errors: { type: Object, default: () => ({}) },
	path: { type: String, default: '' },
	record: { type: String, default: null },
});

const CUSTOM = { images: ImageSection };

const key = computed(() => props.path + props.field.name);
const value = computed({
	get: () => get(props.model, props.field.name),
	set: (next) => set(props.model, props.field.name, next),
});
const error = computed(() => props.errors[key.value]?.[0] ?? null);

/** A section shows red when any field inside it failed. */
function names(fields, prefix = '') {
	return fields.flatMap((child) => [
		...(child.name ? [prefix + child.name] : []),
		...(child.fields ? names(child.fields, child.type === 'repeater' ? `${prefix}${child.name}.` : prefix) : []),
	]);
}
const invalid = computed(() => {
	if (props.field.type !== 'section') return false;
	const inside = names(props.field.fields ?? [], props.path);
	return Object.keys(props.errors).some((failed) => inside.some((name) => failed === name || failed.startsWith(`${name}.`)));
});

const rows = computed(() => get(props.model, props.field.name) ?? []);
// A plain copy: the schema arrives reactive, and `structuredClone` cannot copy
// a proxy — it threw, silently, and *Video hinzufügen* added nothing.
const add = () => rows.value.push(JSON.parse(JSON.stringify(props.field.blank)));
</script>

<template>
	<Field v-if="field.type === 'text'" v-model="value" :label="field.label" :required="field.required" :error="error" />
	<Field v-else-if="field.type === 'number'" v-model="value" type="number" :label="field.label" :required="field.required" :error="error" />
	<Textarea v-else-if="field.type === 'textarea'" v-model="value" :label="field.label" :required="field.required" :rows="field.rows ?? 2" :mono="field.mono" :error="error" />
	<Editor v-else-if="field.type === 'richtext'" v-model="value" :label="field.label" :required="field.required" :error="error" />
	<Select v-else-if="field.type === 'select'" v-model="value" :label="field.label" :options="field.options" :placeholder="field.placeholder" :error="error" />
	<CheckboxGroup v-else-if="field.type === 'checkboxes'" v-model="value" :label="field.label" :required="field.required" :options="field.options" :columns="field.columns" :error="error" />
	<Checkbox v-else-if="field.type === 'checkbox'" v-model="value">{{ field.label }}</Checkbox>

	<!-- *Onlinekurs* / *Publizieren*: side by side, closed by a rule. -->
	<div v-else-if="field.type === 'row'" class="mb-32 flex gap-x-64 border-b border-black pb-24 lg:gap-x-80">
		<FormNode v-for="child in field.fields" :key="child.name" :field="child" :model="model" :errors="errors" :path="path" :record="record" />
	</div>

	<Collapsible v-else-if="field.type === 'section'" :expanded="field.open" :invalid="invalid">
		<template #title>{{ field.label }}</template>
		<div class="mt-16">
			<FormNode v-for="(child, index) in field.fields" :key="child.name ?? index" :field="child" :model="model" :errors="errors" :path="path" :record="record" />
		</div>
	</Collapsible>

	<!-- A course's videos: each row its fields and *Entfernen* over a rule, then
	     legacy's dashed *Video hinzufügen* across the column. -->
	<div v-else-if="field.type === 'repeater'">
		<div v-for="(row, index) in rows" :key="row.uuid ?? `neu-${index}`" class="mb-32 border-b border-black pb-24">
			<template v-for="child in field.fields" :key="child.name">
				<div v-if="child.type === 'checkbox'" class="flex items-center justify-between">
					<FormNode :field="child" :model="row" :errors="errors" :path="`${key}.${index}.`" :record="record" />
					<button type="button" class="transition-colors hover:text-teal sm:text-lg lg:text-xl" @click="rows.splice(index, 1)">Entfernen</button>
				</div>
				<FormNode v-else :field="child" :model="row" :errors="errors" :path="`${key}.${index}.`" :record="record" />
			</template>
		</div>
		<button type="button" class="block w-full border border-dashed border-black py-24 text-center transition-colors hover:border-teal hover:text-teal sm:text-lg lg:text-xl" @click="add">
			{{ field.add ?? 'Hinzufügen' }}
		</button>
	</div>

	<component :is="CUSTOM[field.component]" v-else-if="field.type === 'custom'" :course="record" />
</template>
