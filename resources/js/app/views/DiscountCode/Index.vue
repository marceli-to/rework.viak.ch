<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchDiscountCodes } from '@/api/discountCodes';
import { fold, shortDate } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * *Rabatt-Codes* — legacy's `views/discount/Index.vue` ([[07-dashboard]],
 * step 6): *Gültige Codes* open, *Verwendete oder abgelaufene Codes* closed.
 * A row is the code and its dates, the amount, the remarks, and now how often
 * it was used against how often it may be.
 *
 * **Which group a code is in is the checkout's own question**
 * ([[DiscountCode::isRedeemableOn]]): dates, deleted, and uses against the
 * limit. Legacy split on its `isUsed` flag and `valid_to` instead. Legacy also
 * hid the pencil on the second group; a used code can be edited here, since
 * every booking keeps the amount it got.
 *
 * About a hundred codes: loaded whole, searched in the browser on code,
 * amount and remarks.
 */
const route = useRoute();
const router = useRouter();

const codes = ref([]);
const loading = ref(true);
const error = ref(null);

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const matches = (code) => {
	const text = fold(`${code.code} ${code.amount} ${code.remarks}`);
	return fold(search.value).split(/\s+/).filter(Boolean).every((word) => text.includes(word));
};

const shown = computed(() => (search.value ? codes.value.filter(matches) : codes.value));
const valid = computed(() => shown.value.filter((code) => code.redeemable));
const spent = computed(() => shown.value.filter((code) => !code.redeemable));

const amount = (code) => (code.type === 'percent' ? `${Number(code.amount)}%` : `CHF ${Number(code.amount).toFixed(2)}`);
const dates = (code) =>
	code.valid_from || code.valid_to ? `Gültig: ${shortDate(code.valid_from) || '…'} bis ${shortDate(code.valid_to) || '…'}` : null;
const usage = (code) => (code.usage_limit === '' ? `${code.times_used} eingelöst, unbegrenzt` : `${code.times_used} von ${code.usage_limit} eingelöst`);

onMounted(async () => {
	try {
		codes.value = await fetchDiscountCodes();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Rabatt-Codes" :create="{ name: 'discount-code.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<div v-else class="mt-12">
			<Collapsible v-for="group in [{ title: 'Gültige Codes', rows: valid, open: true }, { title: 'Verwendete oder abgelaufene Codes', rows: spent, open: false }]" :key="`${group.title}-${group.open || search}`" :expanded="group.open || (Boolean(search) && group.rows.length > 0)">
				<template #title>{{ group.title }}<Badge v-if="group.rows.length" variant="solid" class="ml-12">{{ group.rows.length }}</Badge></template>
				<EditableListItem
					v-for="code in group.rows"
					:key="code.uuid"
					:edit="{ name: 'discount-code.edit', params: { uuid: code.uuid } }"
					:dimmed="!code.redeemable"
					wide
				>
					<div class="col-span-12 sm:col-span-4">
						<strong>{{ code.code }}</strong>
						<span v-if="dates(code)" class="block">{{ dates(code) }}</span>
						<div class="mt-8"><Badge>{{ usage(code) }}</Badge></div>
					</div>
					<div class="col-span-12 sm:col-span-2">{{ amount(code) }}</div>
					<div class="col-span-12 pr-40 sm:col-span-6">{{ code.remarks }}</div>
				</EditableListItem>
				<p v-if="!group.rows.length" class="mt-16 sm:mt-32">Keine Codes gefunden.</p>
			</Collapsible>
		</div>
	</section>
</template>
