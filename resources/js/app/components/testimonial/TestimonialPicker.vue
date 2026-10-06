<script setup>
import { computed, ref, watch } from 'vue';
import { useSortable } from '@/composables/useSortable';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import IconTrash from '@/components/icons/Trash.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * Which testimonials a page shows, and in what order ([[HasTestimonials]]):
 * the picked ones as rows, dragged into order and removed with the bin, and a
 * `+` at the top right that opens the rest in a lightbox, as *Teilnehmer
 * hinzufügen* does on a Veranstaltung (Marcel, 2026-10-06; it was a select
 * under the rows). The `title` slot stands left of the `+`. The page decides where a quote stands;
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
const add = (uuid) => !picked.value.includes(uuid) && (picked.value = [...picked.value, uuid]);

// The lightbox stays open after an add, so several can be picked in a row;
// an added quote simply leaves its list.
const adding = ref(false);

/** What is left to add, grouped as the testimonial form groups its subjects. */
const groups = computed(() => {
	const groups = {};
	for (const item of props.testimonials) {
		if (picked.value.includes(item.uuid)) continue;
		(groups[item.subject_label] ??= []).push(item);
	}
	return Object.entries(groups).map(([label, items]) => ({ label, items }));
});
</script>

<template>
	<div>
		<div class="flex items-center justify-between gap-16">
			<slot name="title" />
			<button type="button" class="block hover:text-teal" title="Testimonial hinzufügen" @click="adding = true">
				<IconPlus size="lg" class="block" />
			</button>
		</div>

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

		<Lightbox v-if="adding" title="Testimonial hinzufügen" @close="adding = false">
			<NoResults v-if="!groups.length">Alle Testimonials sind bereits ausgewählt.</NoResults>
			<template v-for="group in groups" :key="group.label">
				<h2 class="mt-24 text-lg font-bold first:mt-0">{{ group.label }}</h2>
				<ul class="text-lg">
					<li v-for="item in group.items" :key="item.uuid" class="flex items-center justify-between gap-16 border-b border-gray-400 py-8">
						<span class="min-w-0">
							{{ item.name }}<template v-if="item.context"> ({{ item.context }})</template>
							<Badge v-if="!item.publish" class="ml-8">nicht publiziert</Badge>
							<span class="line-clamp-2 block text-md text-gray-600" :title="item.quote">„{{ item.quote }}“</span>
						</span>
						<Button class="shrink-0" @click="add(item.uuid)">Hinzufügen</Button>
					</li>
				</ul>
			</template>
		</Lightbox>
	</div>
</template>
