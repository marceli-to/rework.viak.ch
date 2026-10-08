<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchLicences } from '@/api/licences';
import { fetchSettings } from '@/api/settings';
import { fold } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';
import SearchField from '@/components/list/SearchField.vue';
import { KINDS, usage } from '@/views/Setting/kinds';

/**
 * *Software* ([[05-licences]], `/dashboard/software`): the licence catalogue, three levels named
 * *Software* → *Produkt* → *Lizenz* (Marcel, 2026-10-08; the database keeps
 * `software`, `licence_products`, `licence_variants`). *Produkte* first, one
 * collapsible per software as *Einstellungen* draws its lists, the group a form came back from
 * open (`?gruppe=`). Per product its name, its maker, how many licences the
 * dropdown has and the cheapest price, all net. *Nur manuell* marks a product
 * none of whose variants is on the site: VIAK picks it when entering an order.
 *
 * Below, the two lists the catalogue hangs off, *Software* and
 * *Hersteller*, each under its own teal title with a `+` (Marcel,
 * 2026-10-08; they were in *Einstellungen*). The one a form came back from is
 * scrolled to (`?liste=`).
 *
 * The search, in the URL as everywhere (`?suche=`), finds a product by its
 * name, maker, software or a licence's name or article number, and a software
 * or maker by its name. A software with hits opens.
 */
const route = useRoute();
const router = useRouter();

const items = ref([]);
const lists = ref(null);
const loading = ref(true);
const error = ref(null);
const open = computed(() => String(route.query.gruppe ?? ''));
const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

// A software has a page, so its own form ([[SoftwareSchema]]); a maker is a name, the settings form.
const createRoute = (key) => (key === 'software' ? { name: 'licence.software.create' } : { name: 'licence.term.create', params: { kind: key } });
const editRoute = (key, uuid) => (key === 'software' ? { name: 'licence.software.edit', params: { uuid } } : { name: 'licence.term.edit', params: { kind: key, uuid } });

const matches = (...haystack) => {
	const text = fold(haystack.flat().join(' '));
	return fold(search.value).split(/\s+/).filter(Boolean).every((word) => text.includes(word));
};

const shown = computed(() =>
	search.value ? items.value.filter((item) => matches(item.title, item.maker, item.group, item.variants.map((variant) => [variant.title, variant.sku]))) : items.value,
);

const groups = computed(() => {
	const byGroup = new Map();
	for (const item of shown.value) {
		if (!byGroup.has(item.group)) byGroup.set(item.group, []);
		byGroup.get(item.group).push(item);
	}
	return [...byGroup.entries()]
		.map(([title, products]) => ({ title, products }))
		.sort((a, b) => a.title.localeCompare(b.title, 'de', { sensitivity: 'base' }));
});

const terms = (key) => (search.value ? lists.value[key].filter((term) => matches(term.title)) : lists.value[key]);

const price = (value) => Number(value).toFixed(2);

onMounted(async () => {
	try {
		[items.value, lists.value] = await Promise.all([fetchLicences(), fetchSettings()]);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
	if (route.query.liste) requestAnimationFrame(() => document.getElementById(`liste-${route.query.liste}`)?.scrollIntoView());
});
</script>

<template>
	<section>
		<ListHeader title="Produkte" :create="{ name: 'licence.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<template v-else>
			<div class="mt-24 lg:mt-32">
				<NoResults v-if="!shown.length">{{ search ? 'Keine Produkte gefunden.' : 'Noch keine Produkte erfasst.' }}</NoResults>

				<!-- Keyed on the search, so a group with hits opens. -->
				<Collapsible
					v-for="group in groups"
					:key="`${group.title}-${search}`"
					:expanded="open === group.title || Boolean(search)"
					:count="group.products.length"
				>
					<template #title>{{ group.title }}</template>

					<EditableListItem
						v-for="item in group.products"
						:key="item.uuid"
						:edit="{ name: 'licence.edit', params: { uuid: item.uuid } }"
						:dimmed="!item.publish"
						wide
					>
						<div class="col-span-12 sm:col-span-4">{{ item.title }}</div>
						<div class="col-span-12 pr-40 max-sm:mt-8 sm:col-span-8">
							{{ item.maker }}
							<span class="mt-8 flex flex-wrap gap-8">
								<Badge>{{ item.variants.length }} {{ item.variants.length === 1 ? 'Lizenz' : 'Lizenzen' }}</Badge>
								<Badge v-if="item.from">ab CHF {{ price(item.from) }}</Badge>
								<Badge v-if="!item.listed && item.publish">Nur manuell</Badge>
								<Badge v-if="!item.publish" variant="warning">nicht publiziert</Badge>
							</span>
						</div>
					</EditableListItem>
				</Collapsible>
			</div>

			<div v-for="key in ['software', 'manufacturers']" :id="`liste-${key}`" :key="key" class="mt-64 mb-64 scroll-mt-24">
				<ListHeader :title="KINDS[key].title" :create="createRoute(key)" tag="h2" />

				<EditableListItem v-for="term in terms(key)" :key="term.uuid" :edit="editRoute(key, term.uuid)" wide>
					<div class="col-span-12 sm:col-span-4">{{ term.title }}</div>
					<div class="col-span-12 flex flex-wrap gap-8 pr-40 max-sm:mt-8 sm:col-span-8">
						<Badge v-for="text in usage(key, term)" :key="text">{{ text }}</Badge>
					</div>
				</EditableListItem>
				<NoResults v-if="!terms(key).length">{{ search ? 'Keine gefunden.' : 'Noch keine erfasst.' }}</NoResults>
			</div>
		</template>
	</section>
</template>
