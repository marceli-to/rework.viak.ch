<script setup>
import { computed, ref, watch } from 'vue';
import { useSortable } from '@/composables/useSortable';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import IconTrash from '@/components/icons/Trash.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import NoResults from '@/components/ui/NoResults.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * A Vorhaben's offer list ([[Field::offers]], [[04-content]]): a collapsible
 * with the count beside its title, shut until opened (Marcel, 2026-10-09),
 * as the product form's *Lizenzen* ([[VariantSection]]): the picked ones as
 * rows dragged into order, a bin on each, and a `+` under them that opens
 * the rest in a lightbox. Forty courses is too many to scan, so the lightbox
 * has a search.
 *
 * `v-model` is the picked uuids, in order; `options` is every choice there is,
 * `{ value, label, hint, publish }`. An unpublished course can be picked and
 * is marked, since the page leaves it out until it is published.
 */
const picked = defineModel({ type: Array, default: () => [] });

const props = defineProps({
	label: { type: String, default: null },
	options: { type: Array, required: true },
	error: { type: String, default: null },
});

const byValue = computed(() => Object.fromEntries(props.options.map((item) => [item.value, item])));

// The rows as their own list: `useSortable` reorders it in place while
// dragging, and the drop writes the order back to the model.
const rows = ref([]);
watch([picked, byValue], () => (rows.value = (picked.value ?? []).map((value) => byValue.value[value]).filter(Boolean)), { immediate: true });

const { dragging, handlers } = useSortable(rows, (list) => (picked.value = list.map((item) => item.value)));

const remove = (value) => (picked.value = picked.value.filter((item) => item !== value));
const add = (value) => !picked.value.includes(value) && (picked.value = [...picked.value, value]);

// The lightbox stays open after an add, so several can be picked in a row.
const adding = ref(false);
const search = ref('');

/** What is left to add, narrowed by the search. */
const available = computed(() => {
	const term = search.value.trim().toLowerCase();
	return props.options.filter((item) => !picked.value.includes(item.value) && (!term || `${item.hint ?? ''} ${item.label}`.toLowerCase().includes(term)));
});
</script>

<template>
	<Collapsible :count="rows.length" :invalid="!!error">
		<template #title>{{ label }}</template>

		<EditableListItem
			v-for="(item, index) in rows"
			:key="item.value"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
			wide
		>
			<div class="col-span-12 pr-40">
				<span v-if="item.hint" class="mr-8">{{ item.hint }}</span>{{ item.label }}
				<span v-if="!item.publish" class="mt-8 block"><Badge>nicht publiziert</Badge></span>
			</div>
			<button type="button" title="Entfernen" class="absolute top-12 right-0 z-10 block size-18 text-black hover:text-teal" @click="remove(item.value)">
				<IconTrash class="block" />
			</button>
		</EditableListItem>

		<NoResults v-if="!rows.length">Noch nichts ausgewählt.</NoResults>
		<div v-if="error" class="mt-16 text-md text-danger lg:text-lg">{{ error }}</div>

		<div class="mt-24 flex">
			<button type="button" class="block hover:text-teal" :title="`${label} hinzufügen`" @click="adding = true">
				<IconPlus size="lg" class="block" />
			</button>
		</div>

		<Lightbox v-if="adding" :title="`${label} hinzufügen`" narrow @close="adding = false">
			<SearchField v-model="search" class="mb-24" />
			<NoResults v-if="!available.length">Nichts mehr hinzuzufügen.</NoResults>
			<ul class="text-lg">
				<li v-for="item in available" :key="item.value" class="flex items-center justify-between gap-16 border-b border-gray-400 py-12 first:pt-0 last:border-b-0 last:pb-0">
					<div class="min-w-0">
						<p><span v-if="item.hint" class="mr-8">{{ item.hint }}</span>{{ item.label }}</p>
						<span v-if="!item.publish" class="mt-8 block"><Badge variant="warning">nicht publiziert</Badge></span>
					</div>
					<Button class="shrink-0" @click="add(item.value)">Hinzufügen</Button>
				</li>
			</ul>
		</Lightbox>
	</Collapsible>
</template>
