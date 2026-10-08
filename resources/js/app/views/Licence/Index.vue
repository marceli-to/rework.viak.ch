<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchLicences } from '@/api/licences';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Lizenzen* ([[05-licences]]): the catalogue, one collapsible per software
 * group as *Einstellungen* draws its lists, the group a form came back from
 * open (`?gruppe=`). Per product its name, its maker, how many variants the
 * dropdown has and the cheapest price, all net. *Nur manuell* marks a product
 * none of whose variants is on the site: VIAK picks it when entering an order.
 */
const route = useRoute();

const items = ref([]);
const loading = ref(true);
const error = ref(null);
const open = computed(() => String(route.query.gruppe ?? ''));

const groups = computed(() => {
	const byGroup = new Map();
	for (const item of items.value) {
		if (!byGroup.has(item.group)) byGroup.set(item.group, []);
		byGroup.get(item.group).push(item);
	}
	return [...byGroup.entries()]
		.map(([title, products]) => ({ title, products }))
		.sort((a, b) => a.title.localeCompare(b.title, 'de', { sensitivity: 'base' }));
});

const price = (value) => Number(value).toFixed(2);

onMounted(async () => {
	try {
		items.value = await fetchLicences();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Lizenzen" :create="{ name: 'licence.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />
		<NoResults v-else-if="!items.length">Noch keine Lizenzen erfasst.</NoResults>

		<div v-else class="mt-24 lg:mt-32">
			<Collapsible v-for="group in groups" :key="group.title" :expanded="open === group.title" :count="group.products.length">
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
							<Badge>{{ item.variants.length }} {{ item.variants.length === 1 ? 'Variante' : 'Varianten' }}</Badge>
							<Badge v-if="item.from">ab CHF {{ price(item.from) }}</Badge>
							<Badge v-if="!item.listed && item.publish">Nur manuell</Badge>
							<Badge v-if="!item.publish" variant="warning">nicht publiziert</Badge>
						</span>
					</div>
				</EditableListItem>
			</Collapsible>
		</div>
	</section>
</template>
