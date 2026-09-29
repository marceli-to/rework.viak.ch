<script setup>
import { ref } from 'vue';
import { downloadCourseExport } from '@/api/exports';
import Button from '@/components/ui/Button.vue';
import ListHeader from '@/components/list/ListHeader.vue';

/**
 * *Exporte* — legacy's `views/backoffice/export/Index.vue` ([[07-dashboard]],
 * step 7): the title and one button, which downloads every course with its
 * past participants as one Excel file ([[CourseParticipantsExport]]).
 */
const busy = ref(false);
const error = ref(null);

async function download() {
	busy.value = true;
	error.value = null;

	try {
		await downloadCourseExport();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<section>
		<ListHeader title="Exporte" />

		<div class="mt-32 grid grid-cols-12 gap-x-16 lg:gap-x-40">
			<div class="col-span-12 sm:col-span-4">
				<Button class="w-full" :disabled="busy" @click="download">{{ busy ? 'Wird erstellt …' : 'Download Kurse + Teilnehmer (Excel)' }}</Button>
				<p v-if="error" class="mt-12 text-danger">{{ error }}</p>
			</div>
		</div>
	</section>
</template>
