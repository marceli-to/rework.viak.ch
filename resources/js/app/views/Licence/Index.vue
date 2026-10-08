<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchLicences } from '@/api/licences';
import { fetchSettings } from '@/api/settings';
import { fold } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconEdit from '@/components/icons/Edit.vue';
import IconPlus from '@/components/icons/Plus.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * *Software* ([[05-licences]], `/dashboard/software`): the catalogue as the
 * *Kurse* screen draws its courses (Marcel, 2026-10-08), once a software had
 * a page of its own and the separate *Software* list under the products made
 * no sense any more. Software → Produkt → Lizenz:
 *
 * - **One collapsible per software**, by name, dimmed while not published,
 *   with its products counted; the **pencil** on it opens the software's
 *   page form ([[SoftwareSchema]]), as a course's opens the course.
 * - Inside, its **products**: name, maker, how many licences, the cheapest
 *   price net, *Nur manuell* when none is on the site. The **`+`** under them
 *   adds a product already filed under this software. A software only
 *   courses use (SketchUp) has none, and says so.
 * - The title's `+` adds a software. The makers are a list in
 *   *Einstellungen* (Marcel, 2026-10-08).
 *
 * The search (`?suche=`) finds a product by its name, maker or a licence's
 * name or article number, and a software by its name, which shows all of its
 * products. A software with hits opens; so does the one a form came back to
 * (`?software=`).
 */
const route = useRoute();
const router = useRouter();

const items = ref([]);
const lists = ref(null);
const loading = ref(true);
const error = ref(null);
const open = computed(() => String(route.query.software ?? ''));
const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const matches = (...haystack) => {
	const text = fold(haystack.flat().join(' '));
	return fold(search.value).split(/\s+/).filter(Boolean).every((word) => text.includes(word));
};

const productMatches = (item) => matches(item.title, item.maker, item.variants.map((variant) => [variant.title, variant.sku]));

/** Every software, each with its products; while searching, those with a hit. */
const software = computed(() =>
	(lists.value?.software ?? [])
		.map((entry) => {
			const products = items.value.filter((item) => item.software === entry.uuid);
			const named = search.value && matches(entry.title);
			return { ...entry, products: !search.value || named ? products : products.filter(productMatches), hit: named };
		})
		.filter((entry) => !search.value || entry.hit || entry.products.length)
		.sort((a, b) => a.title.localeCompare(b.title, 'de', { sensitivity: 'base' })),
);

const price = (value) => Number(value).toFixed(2);

onMounted(async () => {
	try {
		[items.value, lists.value] = await Promise.all([fetchLicences(), fetchSettings()]);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Software" :create="{ name: 'licence.software.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<template v-else>
			<div class="mt-24 lg:mt-32">
				<NoResults v-if="!software.length">{{ search ? 'Keine Software gefunden.' : 'Noch keine Software erfasst.' }}</NoResults>

				<!-- Keyed on the search, so a software with hits opens. -->
				<Collapsible
					v-for="entry in software"
					:key="`${entry.uuid}-${search}`"
					:expanded="open === entry.uuid || Boolean(search)"
					:dimmed="!entry.publish"
					:count="entry.products.length"
				>
					<template #title>{{ entry.title }}</template>

					<template #action>
						<RouterLink :to="{ name: 'licence.software.edit', params: { uuid: entry.uuid } }" title="Software bearbeiten" class="absolute top-46 right-0 z-10 block size-18 hover:text-teal">
							<IconEdit class="block" />
						</RouterLink>
					</template>

					<EditableListItem
						v-for="item in entry.products"
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
					<NoResults v-if="!entry.products.length">Keine Produkte, nur in Kursen verwendet.</NoResults>

					<div class="mt-24 flex">
						<RouterLink :to="{ name: 'licence.create', query: { software: entry.uuid } }" title="Produkt hinzufügen" class="block hover:text-teal">
							<IconPlus size="lg" class="block" />
						</RouterLink>
					</div>
				</Collapsible>
			</div>
		</template>
	</section>
</template>
