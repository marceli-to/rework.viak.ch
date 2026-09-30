<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchInvoices } from '@/api/invoices';
import { shortDate } from '@/support/format';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';
import NoResults from '@/components/ui/NoResults.vue';

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
 *
 * Legacy's column labels over each list came back on 2026-09-30, after a
 * comparison with its screen.
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

// Bare, as legacy has it: the column says *Betrag*, and every invoice is in francs.
const amount = (invoice) => Number(invoice.grand_total).toFixed(2);

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

		<!-- Legacy's `.collapsible-container`, `mt-12x md:mt-16x`: 24px, 32 from lg. Only *Kurse* has the tight 6px. -->
		<div v-else class="mt-24 lg:mt-32">
			<!-- Keyed on the search, so a search with hits in a closed list opens it. -->
			<Collapsible v-for="group in shown" :key="`${group.status}-${search}`" :expanded="group.open || (Boolean(search) && lists[group.status].total > 0)" :count="lists[group.status].total">
				<template #title>{{ group.title }}</template>

				<!-- Legacy's `stacked-list-item--header` (`lists/_stacked.scss`), measured 2026-09-30: no rule, 8px above
				     and below the labels, 16px at 1.4, white and stuck to the top while the list scrolls under it, and
				     only 8px down to the first row. Only where the list has rows, as legacy, and not on a phone, where
				     the columns stack and legacy's four labels stood alone over nothing. -->
				<div
					v-if="lists[group.status].rows.length"
					class="sticky top-0 z-20 hidden grid-cols-12 gap-x-16 bg-white py-8 sm:mt-32 sm:grid sm:text-lg sm:leading-[1.4] lg:gap-x-40 sm:[&+article]:mt-8!"
				>
					<div class="col-span-12 sm:col-span-2">Nummer</div>
					<div class="col-span-12 sm:col-span-2">Datum</div>
					<div class="col-span-12 sm:col-span-2">Betrag</div>
					<div class="col-span-12 sm:col-span-6">Student</div>
				</div>
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
				<NoResults v-if="!lists[group.status].rows.length">{{ search ? 'Keine Rechnungen gefunden.' : group.empty }}</NoResults>

				<Button v-if="lists[group.status].page < lists[group.status].lastPage" variant="secondary" class="mt-32 w-full" :disabled="lists[group.status].more" @click="loadMore(group.status)">
					{{ lists[group.status].more ? 'Wird geladen …' : `Weitere laden (${lists[group.status].rows.length} von ${lists[group.status].total})` }}
				</Button>
			</Collapsible>
		</div>
	</section>
</template>
