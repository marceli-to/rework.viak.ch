<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchExperts, saveExpertOrder } from '@/api/experts';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import { fold } from '@/support/format';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Experten* — legacy's `views/expert/Index.vue` ([[07-dashboard]], step 6).
 *
 * Two collapsibles: *Aktive Experten*, open and **dragged into the order the
 * Experten page shows**, and *Inaktive Experten*, closed unless a search
 * finds someone in it. A row is the name and city, then the address.
 *
 * Twenty people, so the list loads whole and searches in the browser —
 * legacy's fields: name, e-mail, city. **Dragging is off while a search is
 * active**, as on *Kurse*: a filtered list cannot be put in order.
 */
const route = useRoute();
const router = useRouter();

const active = ref([]);
const inactive = ref([]);
const loading = ref(true);
const error = ref(null);

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const matches = (expert) => {
	const text = fold(`${expert.name} ${expert.email} ${expert.city ?? ''}`);
	return fold(search.value).split(/\s+/).filter(Boolean).every((word) => text.includes(word));
};

const shownActive = computed(() => (search.value ? active.value.filter(matches) : active.value));
const shownInactive = computed(() => (search.value ? inactive.value.filter(matches) : inactive.value));

const { dragging, handlers } = useSortable(active, async (list) => {
	try {
		await saveExpertOrder(list.map((expert) => expert.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

onMounted(async () => {
	try {
		const experts = await fetchExperts();
		active.value = experts.filter((expert) => expert.publish);
		inactive.value = experts.filter((expert) => !expert.publish);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Experten" :create="{ name: 'expert.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<!-- Legacy's `.collapsible-container`, `mt-12x md:mt-16x`: 24px, 32 from lg. Only *Kurse* has the tight 6px. -->
		<div v-else class="mt-24 lg:mt-32">
			<Collapsible expanded>
				<template #title>Aktive Experten</template>
				<EditableListItem
					v-for="(expert, index) in shownActive"
					:key="expert.uuid"
					:edit="{ name: 'expert.edit', params: { uuid: expert.uuid } }"
					:draggable="!search"
					:class="{ 'cursor-grab': !search, 'opacity-40': dragging === index }"
					v-bind="search ? {} : handlers(index)"
					wide
				>
					<div class="col-span-12 sm:col-span-4">{{ expert.name }}<template v-if="expert.city">, {{ expert.city }}</template></div>
					<div class="col-span-12 min-w-0 truncate pr-40 sm:col-span-8">
						<a :href="`mailto:${expert.email}`" class="hover:text-teal" @dragstart.prevent>{{ expert.email }}</a>
					</div>
				</EditableListItem>
				<NoResults v-if="!shownActive.length">Keine aktiven Experten gefunden.</NoResults>
			</Collapsible>

			<!-- Keyed on the search, so a search with hits in here opens it. -->
			<Collapsible :key="`inaktiv-${search}`" :expanded="Boolean(search) && shownInactive.length > 0">
				<template #title>Inaktive Experten</template>
				<EditableListItem
					v-for="expert in shownInactive"
					:key="expert.uuid"
					:edit="{ name: 'expert.edit', params: { uuid: expert.uuid } }"
					dimmed
					wide
				>
					<div class="col-span-12 sm:col-span-4">{{ expert.name }}<template v-if="expert.city">, {{ expert.city }}</template></div>
					<div class="col-span-12 min-w-0 truncate pr-40 sm:col-span-8">
						<a :href="`mailto:${expert.email}`" class="hover:text-teal">{{ expert.email }}</a>
					</div>
				</EditableListItem>
				<NoResults v-if="!shownInactive.length">Keine inaktiven Experten gefunden.</NoResults>
			</Collapsible>
		</div>
	</section>
</template>
