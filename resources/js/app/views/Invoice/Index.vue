<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchInvoices } from '@/api/invoices';
import { shortDate } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * *Rechnungen* — legacy's `views/backoffice/invoice/Index.vue`
 * ([[07-dashboard]], step 7).
 *
 * Legacy's four lists and its row: the number (the PDF), the date, the
 * amount, *Name, Ort*. The pencil on what is still owed, the download icon on
 * what is settled, as legacy has them. What changed:
 *
 * - **Searched and paged on the server** ([[InvoiceController]]): the paid
 *   list is 542 rows. Fifty at a time, *Weitere laden* for the next, and the
 *   search in the URL so the way back from the form keeps it.
 * - ***Fällige Rechnungen* comes second and open**, under the open ones.
 *   Legacy put it third and closed, behind the 542 paid ones, though it is the
 *   list somebody has to act on.
 * - A search opens every list it found something in.
 */
const route = useRoute();
const router = useRouter();

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const GROUPS = [
	{ status: 'offen', title: 'Offene Rechnungen', open: true, empty: 'Es sind keine offenen Rechnungen vorhanden.' },
	{ status: 'faellig', title: 'Fällige Rechnungen', open: true },
	{ status: 'bezahlt', title: 'Bezahlte Rechnungen', open: false },
	{ status: 'storniert', title: 'Stornierte Rechnungen', open: false },
];

const lists = reactive(Object.fromEntries(GROUPS.map((group) => [group.status, { rows: [], page: 1, lastPage: 1, total: 0, more: false }])));
const loading = ref(true);
const error = ref(null);

// A reply to an older search that arrives late is dropped.
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
		const replies = await Promise.all(GROUPS.map(({ status }) => fetchInvoices({ status, search: search.value })));
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
		take(status, await fetchInvoices({ status, search: search.value, page: list.page + 1 }), true);
	} catch (problem) {
		error.value = problem.message;
	} finally {
		list.more = false;
	}
}

// Legacy's list showed every group but the open one only when it had rows.
const shown = computed(() => GROUPS.filter((group) => group.empty || lists[group.status].total > 0));

const amount = (invoice) => `CHF ${Number(invoice.grand_total).toFixed(2)}`;

watch(search, load, { immediate: true });
</script>

<template>
	<section>
		<ListHeader title="Rechnungen">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<div v-else class="mt-12">
			<!-- Keyed on the search, so a search with hits in a closed list opens it. -->
			<Collapsible v-for="group in shown" :key="`${group.status}-${search}`" :expanded="group.open || (Boolean(search) && lists[group.status].total > 0)">
				<template #title>{{ group.title }}<Badge v-if="lists[group.status].total" variant="solid" class="ml-12">{{ lists[group.status].total }}</Badge></template>

				<EditableListItem
					v-for="invoice in lists[group.status].rows"
					:key="invoice.uuid"
					:edit="invoice.editable ? { name: 'backoffice.invoice.edit', params: { uuid: invoice.uuid } } : null"
					:download="invoice.document"
					wide
				>
					<div class="col-span-12 sm:col-span-2">
						<a v-if="invoice.document" :href="invoice.document" title="Herunterladen" class="hover:text-teal">{{ invoice.number }}</a>
						<template v-else>{{ invoice.number }}</template>
					</div>
					<div class="col-span-12 sm:col-span-2">{{ shortDate(invoice.date) }}</div>
					<div class="col-span-12 sm:col-span-2">{{ amount(invoice) }}</div>
					<div class="col-span-12 pr-40 sm:col-span-6">
						<template v-if="invoice.student">{{ invoice.student.name }}<template v-if="invoice.student.city">, {{ invoice.student.city }}</template></template>
					</div>
				</EditableListItem>
				<p v-if="!lists[group.status].rows.length" class="mt-16 sm:mt-32">{{ search ? 'Keine Rechnungen gefunden.' : group.empty }}</p>

				<Button v-if="lists[group.status].page < lists[group.status].lastPage" variant="secondary" class="mt-32 w-full" :disabled="lists[group.status].more" @click="loadMore(group.status)">
					{{ lists[group.status].more ? 'Wird geladen …' : `Weitere laden (${lists[group.status].rows.length} von ${lists[group.status].total})` }}
				</Button>
			</Collapsible>
		</div>
	</section>
</template>
