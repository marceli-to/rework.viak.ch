<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCompanyCourses } from '@/api/companyCourses';
import { fold, shortDate } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Firmenkurse* ([[16-firmenkurse]]): the coming ones open, the past ones
 * shut, as *Rabatt-Codes* splits its two. A row is the company and the
 * course, its days, and the invoice as a badge.
 *
 * A handful a year: loaded whole, searched in the browser.
 */
const route = useRoute();
const router = useRouter();

const courses = ref([]);
const loading = ref(true);
const error = ref(null);

const search = computed(() => String(route.query.suche ?? ''));
const setSearch = (value) => router.replace({ query: value ? { suche: value } : {} });

const words = computed(() => fold(search.value).split(/\s+/).filter(Boolean));
const matches = (text) => words.value.every((word) => fold(text).includes(word));

const today = new Date().toISOString().slice(0, 10);
const shown = computed(() => courses.value.filter((course) => matches(`${course.name} ${course.company} ${course.city} ${course.location} ${course.invoice?.number ?? ''}`)));
const upcoming = computed(() => shown.value.filter((course) => !course.last_date || course.last_date >= today).sort((a, b) => (a.first_date ?? '').localeCompare(b.first_date ?? '')));
const past = computed(() => shown.value.filter((course) => course.last_date && course.last_date < today));

const dates = (course) =>
	!course.first_date ? null : course.first_date === course.last_date ? shortDate(course.first_date) : `${shortDate(course.first_date)} – ${shortDate(course.last_date)}`;

const STATUS = { OPEN: 'offen', OVERDUE: 'fällig', PAID: 'bezahlt' };
const invoiced = (course) => (course.invoice && course.invoice.status !== 'CANCELLED' ? `Rechnung ${course.invoice.number} ${STATUS[course.invoice.status]}` : null);

const counted = (count, one, many) => `${count} ${count === 1 ? one : many}`;

onMounted(async () => {
	try {
		courses.value = await fetchCompanyCourses();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Firmenkurse" :create="{ name: 'company-course.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="setSearch" />
			</template>
		</ListHeader>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<!-- Legacy's `.collapsible-container`, `mt-12x md:mt-16x`: 24px, 32 from lg. -->
		<div v-else class="mt-24 lg:mt-32">
			<Collapsible
				v-for="group in [{ title: 'Bevorstehende Firmenkurse', rows: upcoming, open: true }, { title: 'Vergangene Firmenkurse', rows: past, open: false }]"
				:key="`${group.title}-${search}`"
				:expanded="group.open || (Boolean(search) && group.rows.length > 0)"
				:count="group.rows.length"
			>
				<template #title>{{ group.title }}</template>
				<EditableListItem v-for="course in group.rows" :key="course.uuid" :edit="{ name: 'company-course.edit', params: { uuid: course.uuid } }" wide>
					<div class="col-span-12 sm:col-span-4">
						<strong>{{ course.company }}</strong>
						<span class="block">{{ course.name }}</span>
					</div>
					<div class="col-span-12 sm:col-span-3">
						{{ dates(course) }}<template v-if="course.location"><br />{{ course.location }}</template>
					</div>
					<div class="col-span-12 flex flex-wrap content-start gap-8 pr-40 max-sm:mt-8 sm:col-span-5">
						<Badge v-if="course.participants">{{ counted(Number(course.participants), 'Person', 'Personen') }}</Badge>
						<Badge v-if="invoiced(course)" :variant="course.invoice.status === 'OVERDUE' ? 'danger' : undefined">{{ invoiced(course) }}</Badge>
						<Badge v-else>Nicht verrechnet</Badge>
					</div>
				</EditableListItem>
				<NoResults v-if="!group.rows.length">Keine Firmenkurse gefunden.</NoResults>
			</Collapsible>
		</div>
	</section>
</template>
