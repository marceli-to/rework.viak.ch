<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchCourses, saveCourseOrder } from '@/api/courses';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import { fold } from '@/support/format';
import Collapsible from '@/components/ui/Collapsible.vue';
import Loading from '@/components/ui/Loading.vue';
import EventLine from '@/components/course/EventLine.vue';
import EventRow from '@/components/course/EventRow.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import SearchField from '@/components/list/SearchField.vue';
import IconArrowRight from '@/components/icons/ArrowRight.vue';
import IconArrowSwitcher from '@/components/icons/ArrowSwitcher.vue';
import IconEdit from '@/components/icons/Edit.vue';
import IconPlus from '@/components/icons/Plus.vue';

/**
 * *Kurse* — legacy's one screen with two modes (`views/course/Index.vue`),
 * kept as one at Marcel's call on 2026-09-24 ([[07-dashboard]]).
 *
 * - **Chronological**, the default: every upcoming course date across the
 *   catalogue, soonest first.
 * - **Courses**: the catalogue as an accordion in its public order, each course
 *   holding its dates — **dragged into a new order**, which is the order the
 *   site's course list shows.
 *
 * The switch names the *other* mode, as legacy's does: *Kurse* while the dates
 * are showing, *Veranstaltungen* while the courses are. Mode and search live in
 * the URL, so reload and the back button keep them.
 *
 * **Dragging is off while a search is active.** Legacy allowed it and saved the
 * wrong list — it dragged the full list and rendered the filtered one.
 */
const route = useRoute();
const router = useRouter();

const courses = ref([]);
const loading = ref(true);
const error = ref(null);

const mode = computed(() => (route.query.modus === 'kurse' ? 'courses' : 'chronological'));
const search = computed(() => String(route.query.suche ?? ''));

const setQuery = (changes) => {
	const query = { ...route.query, ...changes };
	for (const key of Object.keys(query)) if (!query[key]) delete query[key];
	router.replace({ query });
};

/** Legacy's search: every word must match somewhere — number, title, experts, place, date. */
const matches = (haystack) => {
	const words = fold(search.value).split(/\s+/).filter(Boolean);
	const text = fold(haystack);
	return words.every((word) => text.includes(word));
};

const events = computed(() =>
	courses.value
		.flatMap((course) => course.events.map((event) => ({ event, course })))
		.filter(({ event, course }) =>
			matches([course.number, course.title, event.experts.join(' '), event.online ? 'online' : event.location, event.date].join(' ')),
		)
		.sort((a, b) => (a.event.date < b.event.date ? -1 : a.event.date > b.event.date ? 1 : 0)),
);

const visibleCourses = computed(() => (search.value ? courses.value.filter((course) => matches(`${course.number} ${course.title}`)) : courses.value));

const { dragging, handlers } = useSortable(courses, async (list) => {
	try {
		await saveCourseOrder(list.map((course) => course.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

onMounted(async () => {
	try {
		courses.value = await fetchCourses();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Kurse" :create="{ name: 'course.create' }">
			<template #search>
				<SearchField :model-value="search" @update:model-value="(value) => setQuery({ suche: value })" />
			</template>
		</ListHeader>

		<!-- `.btn-switcher`: 16px, regular, the icon 8px before the word. -->
		<div class="mt-32">
			<button
				type="button"
				class="inline-flex min-h-28 items-center gap-8 text-lg font-normal hover:text-teal"
				@click="setQuery({ modus: mode === 'chronological' ? 'kurse' : '' })"
			>
				<IconArrowSwitcher />
				<span>{{ mode === 'chronological' ? 'Kurse' : 'Veranstaltungen' }}</span>
			</button>
		</div>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<template v-else-if="mode === 'chronological'">
			<EventLine v-for="{ event, course } in events" :key="event.uuid" :event="event" :course="course" />
			<p v-if="!events.length" class="mt-32">Keine Kursdaten gefunden.</p>
		</template>

		<div v-else class="mt-12">
			<Collapsible
				v-for="(course, index) in visibleCourses"
				:key="course.uuid"
				:dimmed="!course.publish"
				:draggable="!search"
				:class="{ 'cursor-grab': !search, 'opacity-40': dragging === index }"
				v-bind="search ? {} : handlers(index)"
			>
				<template #title>
					<!-- Legacy's space and 12px margin both, as it renders them. -->
					{{ course.number }} <span class="ml-12 inline-block font-normal">{{ course.title }}</span>
				</template>

				<template #action>
					<RouterLink :to="{ name: 'course.edit', params: { uuid: course.uuid } }" title="Kurs bearbeiten" class="absolute top-46 right-0 z-10 block size-18 hover:text-teal">
						<IconEdit class="block" />
					</RouterLink>
				</template>

				<EventRow v-for="event in course.events" :key="event.uuid" :event="event" />
				<p v-if="!course.events.length" class="mt-16 sm:mt-32">Es sind keine Veranstaltungen vorhanden.</p>

				<div class="mt-24 flex items-center justify-between">
					<RouterLink :to="{ name: 'event.create', params: { course: course.uuid } }" title="Neues Kursdatum" class="block hover:text-teal">
						<IconPlus size="lg" class="block" />
					</RouterLink>
					<RouterLink :to="{ name: 'course.events', params: { uuid: course.uuid } }" title="Alle Kursdaten" class="block hover:text-teal">
						<IconArrowRight size="sm" class="[&_svg]:w-20" />
					</RouterLink>
				</div>
			</Collapsible>
			<p v-if="!visibleCourses.length" class="mt-32">Keine Kurse gefunden.</p>
		</div>
	</section>
</template>
