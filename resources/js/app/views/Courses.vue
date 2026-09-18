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
		<header class="mb-24">
			<h1 class="text-3xl font-semibold tracking-tight text-black">Kurse</h1>
			<p class="mt-4 text-md text-gray-600">{{ courses.length }} Kurse</p>
		</header>

		<p v-if="error" class="mb-16 bg-danger/10 px-16 py-8 text-md text-danger">{{ error }}</p>
		<p v-if="loading" class="text-md text-gray-600">Wird geladen …</p>

		<div v-else class="grid gap-12 sm:grid-cols-2 xl:grid-cols-3">
			<article v-for="course in courses" :key="course.uuid" class="border border-gray-400 p-16">
				<div class="flex items-start justify-between gap-12">
					<h2 class="text-md font-semibold leading-snug text-black">{{ course.title?.de }}</h2>
					<span class="shrink-0 text-xs text-gray-400">#{{ course.number }}</span>
				</div>

				<p class="mt-8 text-xs text-gray-600">CHF {{ course.fee }} · {{ course.events_count }} Termine</p>

				<div v-if="course.software?.length" class="mt-12 flex flex-wrap gap-4">
					<span v-for="item in course.software" :key="item.uuid" class="bg-gray-200 px-6 py-2 text-[11px] text-gray-600">
						{{ item.title?.de }}
					</span>
				</div>

				<span v-if="!course.publish" class="mt-12 inline-block bg-gray-200 px-8 py-2 text-[11px] text-gray-400">
					nicht publiziert
				</span>
			</article>
		</div>
	</section>
</template>
