<script setup>
import { deleteCourse, fetchCourse, saveCourse } from '@/api/courses';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Kurs erfassen* / *Kurs bearbeiten* — legacy's `views/course/Form.vue`
 * ([[07-dashboard]], step 2), on the field kit since step 5: the fields are
 * [[CourseSchema]], the frame is [[ResourceForm]].
 *
 * What differs from legacy, and why, is recorded on the schema and in
 * `07-dashboard.md`: no DE / EN switch, the images saving on their own, videos
 * saving with the form, deleting refused while a date has bookings.
 */
const list = { name: 'courses', query: { modus: 'kurse' } };
</script>

<template>
	<ResourceForm
		schema="course"
		:load="fetchCourse"
		:save="saveCourse"
		:remove="deleteCourse"
		:list="list"
		:edit="(uuid) => ({ name: 'course.edit', params: { uuid } })"
		noun="Kurs"
		:titles="{ create: 'Kurs erfassen', edit: 'Kurs bearbeiten' }"
		:deletion="{ title: 'Kurs löschen', text: 'Mit dieser Aktion wird der Kurs inklusive aller Kursdaten gelöscht.', question: () => 'Der Kurs wird mit allen Kursdaten gelöscht.' }"
		:blocked="(meta) => (meta.has_bookings ? 'Dieser Kurs hat Buchungen und kann nicht gelöscht werden. Setze ihn stattdessen auf nicht publiziert.' : null)"
	/>
</template>
