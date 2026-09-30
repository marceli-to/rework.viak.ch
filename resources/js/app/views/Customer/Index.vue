<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCustomers } from '@/api/customers';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Studenten* — legacy's `views/student/Index.vue` ([[07-dashboard]], step 6).
 *
 * *Aktive Kunden*: name and city, the address, the phone, each row with
 * the pencil and the arrow to the student's page. **Searched and paged on the
 * server**, where legacy loaded all 570: fifty at a time, *Weitere laden* for
 * the next. The search is in the URL, so the way back from a form keeps it.
 *
 * *Deaktivierte Kunden* below, closed unless a search finds someone in it
 * — the accounts that are switched off instead of deleted (#16).
 */
const route = useRoute();
const router = useRouter();

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const active = ref([]);
const deactivated = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const loading = ref(true);
const more = ref(false);
const error = ref(null);

// A reply to an older search that arrives late is dropped.
let asked = 0;

async function load() {
	const ask = ++asked;
	loading.value = true;
	error.value = null;

	try {
		const [first, off] = await Promise.all([fetchCustomers({ search: search.value }), fetchCustomers({ search: search.value, deactivated: true })]);
		if (ask !== asked) return;

		active.value = first.data;
		page.value = first.meta.current_page;
		lastPage.value = first.meta.last_page;
		total.value = first.meta.total;
		deactivated.value = off.data;
	} catch (problem) {
		if (ask === asked) error.value = problem.message;
	} finally {
		if (ask === asked) loading.value = false;
	}
}

async function loadMore() {
	more.value = true;
	try {
		const next = await fetchCustomers({ search: search.value, page: page.value + 1 });
		active.value.push(...next.data);
		page.value = next.meta.current_page;
		lastPage.value = next.meta.last_page;
	} catch (problem) {
		error.value = problem.message;
	} finally {
		more.value = false;
	}
}

watch(search, load, { immediate: true });
</script>

<template>
	<section>
		<ListHeader title="Kunden" :create="{ name: 'customer.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<!-- Legacy's `.collapsible-container`, `mt-12x md:mt-16x`: 24px, 32 from lg. Only *Kurse* has the tight 6px. -->
		<div v-else class="mt-24 lg:mt-32">
			<Collapsible expanded :count="total">
				<template #title>Aktive Kunden</template>
				<EditableListItem
					v-for="customer in active"
					:key="customer.uuid"
					:edit="{ name: 'customer.edit', params: { uuid: customer.uuid } }"
					:show="{ name: 'customer.show', params: { uuid: customer.uuid } }"
					wide
				>
					<div class="col-span-12 sm:col-span-4">{{ customer.name }}<template v-if="customer.city">, {{ customer.city }}</template></div>
					<div class="col-span-12 min-w-0 truncate sm:col-span-4">
						<a :href="`mailto:${customer.email}`" class="hover:text-teal">{{ customer.email }}</a>
					</div>
					<div class="col-span-12 pr-40 sm:col-span-4">
						<a v-if="customer.phone" :href="`tel:${customer.phone.replace(/\s+/g, '')}`" class="hover:text-teal">{{ customer.phone }}</a>
					</div>
				</EditableListItem>
				<NoResults v-if="!active.length">Keine Kunden gefunden.</NoResults>

				<Button v-if="page < lastPage" variant="secondary" class="mt-32 w-full" :disabled="more" @click="loadMore">
					{{ more ? 'Wird geladen …' : `Weitere laden (${active.length} von ${total})` }}
				</Button>
			</Collapsible>

			<!-- Keyed on the search, so a search with hits in here opens it. -->
			<Collapsible v-if="deactivated.length" :key="`deaktiviert-${search}`" :expanded="Boolean(search)" :count="deactivated.length">
				<template #title>Deaktivierte Kunden</template>
				<EditableListItem
					v-for="customer in deactivated"
					:key="customer.uuid"
					:edit="{ name: 'customer.edit', params: { uuid: customer.uuid } }"
					:show="{ name: 'customer.show', params: { uuid: customer.uuid } }"
					dimmed
					wide
				>
					<div class="col-span-12 sm:col-span-4">{{ customer.name }}<template v-if="customer.city">, {{ customer.city }}</template></div>
					<div class="col-span-12 min-w-0 truncate sm:col-span-4">{{ customer.email }}</div>
					<div class="col-span-12 pr-40 sm:col-span-4">{{ customer.phone }}</div>
				</EditableListItem>
			</Collapsible>
		</div>
	</section>
</template>
