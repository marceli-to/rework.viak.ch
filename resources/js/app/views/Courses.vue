<script setup>
import { ref, onMounted } from 'vue';
import { fetchCourses } from '@/api/courses';

const courses = ref([]);
const loading = ref(true);
const error = ref(null);

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
		<header class="mb-6">
			<h1 class="text-2xl font-semibold tracking-tight text-ink">Kurse</h1>
			<p class="mt-1 text-sm text-muted">{{ courses.length }} Kurse</p>
		</header>

		<p v-if="error" class="mb-4 bg-danger/10 px-4 py-2 text-sm text-danger">{{ error }}</p>
		<p v-if="loading" class="text-sm text-muted">Wird geladen …</p>

		<div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
			<article v-for="course in courses" :key="course.uuid" class="border border-line p-4">
				<div class="flex items-start justify-between gap-3">
					<h2 class="text-sm font-semibold leading-snug text-ink">{{ course.title?.de }}</h2>
					<span class="shrink-0 text-xs text-faint">#{{ course.number }}</span>
				</div>

				<p class="mt-2 text-xs text-muted">CHF {{ course.fee }} · {{ course.events_count }} Termine</p>

				<div v-if="course.software?.length" class="mt-3 flex flex-wrap gap-1">
					<span v-for="item in course.software" :key="item.uuid" class="bg-paper px-1.5 py-0.5 text-[11px] text-muted">
						{{ item.title?.de }}
					</span>
				</div>

				<span v-if="!course.publish" class="mt-3 inline-block bg-light px-2 py-0.5 text-[11px] text-faint">
					nicht publiziert
				</span>
			</article>
		</div>
	</section>
</template>
