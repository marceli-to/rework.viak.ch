<script setup>
import { computed, ref, watch } from 'vue';
import { useSortable } from '@/composables/useSortable';
import Badge from '@/components/ui/Badge.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconTrash from '@/components/icons/Trash.vue';
import NoResults from '@/components/ui/NoResults.vue';
import Select from '@/components/form/Select.vue';

/**
 * Which testimonials a page shows, and in what order ([[HasTestimonials]]):
 * the picked ones as rows, dragged into order and removed with the bin, and a
 * select under them to add another. The page decides where a quote stands;
 * the testimonial form only reads it (*Verwendet auf*).
 *
 * `v-model` is the picked uuids, in order; `testimonials` is every one there
 * is. An unpublished quote can be picked and is marked, since the page leaves
 * it out until it is published.
 */
const picked = defineModel({ type: Array, required: true });

const props = defineProps({
	testimonials: { type: Array, required: true },
});

const byUuid = computed(() => Object.fromEntries(props.testimonials.map((item) => [item.uuid, item])));

// The rows as their own list: `useSortable` reorders it in place while
// dragging, and the drop writes the order back to the model.
const rows = ref([]);
watch([picked, byUuid], () => (rows.value = picked.value.map((uuid) => byUuid.value[uuid]).filter(Boolean)), { immediate: true });

const { dragging, handlers } = useSortable(rows, (list) => (picked.value = list.map((item) => item.uuid)));

const remove = (uuid) => (picked.value = picked.value.filter((item) => item !== uuid));
const add = (uuid) => uuid && !picked.value.includes(uuid) && (picked.value = [...picked.value, uuid]);

/** What is left to add, grouped as the testimonial form groups its subjects. */
const options = computed(() => {
	const groups = {};
	for (const item of props.testimonials) {
		if (picked.value.includes(item.uuid)) continue;
		(groups[item.subject_label] ??= []).push({ value: item.uuid, label: `${item.name}: „${excerpt(item.quote)}“` });
	}
	return Object.entries(groups).map(([label, options]) => ({ label, options }));
});

const excerpt = (text, max = 60) => (text.length > max ? `${text.slice(0, max).replace(/\s+\S*$/, '')} …` : text);
</script>

<template>
	<div>
		<EditableListItem
			v-for="(item, index) in rows"
			:key="item.uuid"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
			wide
		>
			<div class="col-span-12 sm:col-span-4">
				{{ item.name }}<template v-if="item.context"> ({{ item.context }})</template>
				<span v-if="!item.publish" class="mt-8 block"><Badge>nicht publiziert</Badge></span>
			</div>
			<div class="col-span-12 pr-40 sm:col-span-8">
				<p class="line-clamp-2" :title="item.quote">„{{ item.quote }}“</p>
			</div>
			<button type="button" title="Entfernen" class="absolute top-12 right-0 z-10 block size-18 text-black hover:text-teal" @click="remove(item.uuid)">
				<IconTrash class="block" />
			</button>
		</EditableListItem>

		<NoResults v-if="!rows.length">Noch keine Testimonials ausgewählt.</NoResults>

		<div class="mt-32">
			<Select
				:model-value="''"
				label="Testimonial hinzufügen"
				:options="options"
				placeholder="Bitte wählen..."
				@update:model-value="add"
			/>
		</div>
	</div>
</template>
