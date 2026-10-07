<script setup>
import { deleteProject, fetchProject, saveProject } from '@/api/projects';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Vorhaben erfassen* / *bearbeiten* ([[04-content]]): the fields are
 * [[ProjectSchema]], the courses picked and ordered in the offer picker.
 */
</script>

<template>
	<ResourceForm
		schema="project"
		:load="fetchProject"
		:save="saveProject"
		:remove="deleteProject"
		:list="{ name: 'content.projects' }"
		:edit="(uuid) => ({ name: 'content.project.edit', params: { uuid } })"
		noun="Vorhaben"
		:titles="{ create: 'Vorhaben erfassen', edit: 'Vorhaben bearbeiten' }"
		:deletion="{
			title: 'Vorhaben löschen',
			text: 'Mit dieser Aktion wird das Vorhaben gelöscht. Die Kurse bleiben.',
			question: (form) => form.title,
		}"
	/>
</template>
