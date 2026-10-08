<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrders } from '@/api/licenceOrders';
import { shortDate } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import CustomerPicker from '@/components/order/CustomerPicker.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';
import PaymentBadge from '@/components/order/PaymentBadge.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * *Bestellungen*: the licence orders and **the dispatch worklist**
 * ([[05-licences]], [[LicenceOrderController]]). Fulfilment is a person at
 * VIAK ordering from the reseller and forwarding the licence, so an order is
 * *offen* until each line is marked sent, on the order's own page.
 *
 * *Offene Bestellungen* comes oldest first, as a queue is worked; the sent
 * ones newest first, paged as *Rechnungen*. Each row says whether it is paid,
 * since an order may be sent before its invoice is (open question #2). The
 * `+` asks for the customer first: an order taken by mail or phone belongs to
 * an account.
 */
const route = useRoute();
const router = useRouter();

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const GROUPS = [
	{ status: 'offen', title: 'Offene Bestellungen', open: true, empty: 'Es sind keine offenen Bestellungen vorhanden.' },
	{ status: 'versendet', title: 'Versendete Bestellungen', open: false, empty: 'Es sind noch keine Bestellungen versendet.' },
];

const lists = reactive(Object.fromEntries(GROUPS.map((group) => [group.status, { rows: [], page: 1, lastPage: 1, total: 0, more: false }])));
const loading = ref(true);
const error = ref(null);

let asked = 0;

function take(status, reply, append = false) {
	const list = lists[status];
	list.rows = append ? [...list.rows, ...reply.data] : reply.data;
	list.page = reply.meta.current_page;
	list.lastPage = reply.meta.last_page;
	list.total = reply.meta.total;
}

async function load() {
	const ask = ++asked;
	loading.value = true;
	error.value = null;

	try {
		const replies = await Promise.all(GROUPS.map(({ status }) => fetchOrders({ status, search: search.value })));
		if (ask !== asked) return;

		GROUPS.forEach(({ status }, index) => take(status, replies[index]));
	} catch (problem) {
		if (ask === asked) error.value = problem.message;
	} finally {
		if (ask === asked) loading.value = false;
	}
}

async function loadMore(status) {
	const list = lists[status];
	list.more = true;

	try {
		take(status, await fetchOrders({ status, search: search.value, page: list.page + 1 }), true);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		list.more = false;
	}
}

const picking = ref(false);
const pick = (customer) => router.push({ name: 'backoffice.order.create', params: { customer: customer.uuid } });

const amount = (order) => Number(order.total).toFixed(2);

watch(search, load, { immediate: true });
</script>

<template>
	<section>
		<ListHeader title="Bestellungen">
			<template #create>
				<button type="button" class="mt-4 block size-16 hover:text-teal" title="Bestellung erfassen" @click="picking = true">
					<IconPlus size="lg" class="block" />
				</button>
			</template>
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<div v-else class="mt-24 lg:mt-32">
			<Collapsible v-for="group in GROUPS" :key="`${group.status}-${search}`" :expanded="group.open || (Boolean(search) && lists[group.status].total > 0)" :count="lists[group.status].total">
				<template #title>{{ group.title }}</template>

				<!-- *Rechnungen*' column labels, stuck to the top while the list scrolls. -->
				<div
					v-if="lists[group.status].rows.length"
					class="sticky top-0 z-20 hidden grid-cols-12 gap-x-16 bg-white py-8 sm:mt-32 sm:grid sm:text-lg sm:leading-[1.4] lg:gap-x-40 sm:[&+article]:mt-8!"
				>
					<div class="col-span-12 sm:col-span-2">Nummer</div>
					<div class="col-span-12 sm:col-span-2">Datum</div>
					<div class="col-span-12 sm:col-span-3">Kunde</div>
					<div class="col-span-12 sm:col-span-5">Software</div>
				</div>
				<EditableListItem v-for="order in lists[group.status].rows" :key="order.uuid" :show="{ name: 'backoffice.order.show', params: { uuid: order.uuid } }" wide>
					<div class="col-span-12 sm:col-span-2">
						<RouterLink :to="{ name: 'backoffice.order.show', params: { uuid: order.uuid } }" class="hover:text-teal">{{ order.number }}</RouterLink>
					</div>
					<div class="col-span-12 sm:col-span-2">{{ shortDate(order.date) }}</div>
					<div class="col-span-12 sm:col-span-3">{{ order.customer.name }}<template v-if="order.customer.city">, {{ order.customer.city }}</template></div>
					<div class="col-span-12 pr-40 sm:col-span-5">
						<div v-for="(line, index) in order.lines" :key="index">{{ line }}</div>
						<div class="mt-8 flex flex-wrap gap-8">
							<PaymentBadge :payment="order.payment" />
							<Badge v-if="order.payment !== 'free'" variant="solid">CHF {{ amount(order) }}</Badge>
							<Badge v-if="group.status === 'offen' && order.open < order.lines.length">{{ order.lines.length - order.open }} von {{ order.lines.length }} versendet</Badge>
						</div>
					</div>
				</EditableListItem>
				<NoResults v-if="!lists[group.status].rows.length">{{ search ? 'Keine Bestellungen gefunden.' : group.empty }}</NoResults>

				<Button v-if="lists[group.status].page < lists[group.status].lastPage" variant="secondary" class="mt-32 w-full" :disabled="lists[group.status].more" @click="loadMore(group.status)">
					{{ lists[group.status].more ? 'Wird geladen …' : `Weitere laden (${lists[group.status].rows.length} von ${lists[group.status].total})` }}
				</Button>
			</Collapsible>
		</div>

		<CustomerPicker v-if="picking" title="Bestellung erfassen für" action="Weiter" @pick="pick" @close="picking = false" />
	</section>
</template>
