<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { fetchVariants, saveVariantOrder } from '@/api/licences';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Varianten* on the licence form ([[05-licences]]): a collapsible list, each
 * row a pencil to its own form and a `+` under it, as *Einstellungen* draws
 * its lists (Marcel, 2026-10-08). Rows drag into the order the product's
 * dropdown shows; that saves at once and is not part of the form's save.
 *
 * Each row names the variant, then its Lizenztyp (*Demo* for none) and
 * Nutzung, since the client's names are often the vendor's word alone
 * (*floating*), then article number and price as on the product list.
 *
 * On a product not yet saved there is nothing to hang a variant on, so the
 * section says so.
 */
const props = defineProps({
	// The product's uuid; null while it is not saved yet.
	record: { type: String, default: null },
});

const items = ref([]);
const loading = ref(false);
const error = ref(null);

const { dragging, handlers } = useSortable(items, async (list) => {
	try {
		await saveVariantOrder(props.record, list.map((item) => item.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

const price = (value) => Number(value).toFixed(2);

onMounted(async () => {
	if (!props.record) return;
	loading.value = true;
	try {
		items.value = await fetchVariants(props.record);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<Collapsible expanded :count="items.length">
		<template #title>Varianten</template>

		<p v-if="!record" class="mt-16 sm:text-lg lg:text-xl">Varianten können nach dem ersten Speichern erfasst werden.</p>
		<template v-else>
			<p v-if="error" class="mt-16 text-danger">{{ error }}</p>
			<Loading v-else-if="loading" class="mt-16" />
			<NoResults v-else-if="!items.length">Noch keine Varianten erfasst.</NoResults>

			<EditableListItem
				v-for="(item, index) in items"
				:key="item.uuid"
				:edit="{ name: 'licence.variant.edit', params: { product: record, uuid: item.uuid } }"
				:dimmed="!item.listed"
				draggable="true"
				class="cursor-grab"
				:class="{ 'opacity-40': dragging === index }"
				v-bind="handlers(index)"
				wide
			>
				<div class="col-span-12 sm:col-span-6">{{ item.title }}</div>
				<div class="col-span-12 pr-40 max-sm:mt-8 sm:col-span-6">
					{{ item.labels.join(', ') }}
					<span class="mt-8 flex flex-wrap gap-8">
						<Badge>{{ item.sku }}</Badge>
						<Badge>CHF {{ price(item.price) }}</Badge>
						<Badge v-if="item.min_quantity">mind. {{ item.min_quantity }}</Badge>
						<Badge v-if="!item.listed">Nur manuell</Badge>
					</span>
				</div>
			</EditableListItem>

			<div class="mt-24 flex">
				<RouterLink :to="{ name: 'licence.variant.create', params: { product: record } }" title="Variante hinzufügen" class="block hover:text-teal">
					<IconPlus size="lg" class="block" />
				</RouterLink>
			</div>
		</template>
	</Collapsible>
</template>
