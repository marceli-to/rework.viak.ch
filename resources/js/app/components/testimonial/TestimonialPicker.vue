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

/** What is left to add, in the testimonials' own order. */
const available = computed(() => props.testimonials.filter((item) => !picked.value.includes(item.uuid)));
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

		<!-- Each quote as the row it will become: who said it, the quote at the
		     same size, then what it is about as a badge beside the button. -->
		<Lightbox v-if="adding" title="Testimonial hinzufügen" narrow @close="adding = false">
			<NoResults v-if="!available.length">Alle Testimonials sind bereits ausgewählt.</NoResults>
			<ul class="text-lg">
				<li v-for="item in available" :key="item.uuid" class="border-b border-gray-400 py-12 first:pt-0 last:border-b-0 last:pb-0">
					<p class="font-bold">{{ item.name }}</p>
					<p v-if="item.context">{{ item.context }}</p>
					<p class="mt-8 line-clamp-3" :title="item.quote">„{{ item.quote }}“</p>
					<div class="mt-12 flex items-center justify-between gap-16">
						<div class="flex min-w-0 flex-wrap gap-8">
							<Badge>{{ item.subject_label }}</Badge>
							<Badge v-if="!item.publish" variant="warning">nicht publiziert</Badge>
						</div>
						<Button class="shrink-0" @click="add(item.uuid)">Hinzufügen</Button>
					</div>
				</li>
			</ul>
		</Lightbox>
	</div>
</template>
