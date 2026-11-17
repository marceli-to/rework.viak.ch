<script setup>
import { deleteNews, fetchNews, saveNews } from '@/api/news';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *News erfassen* / *bearbeiten* ([[NewsSchema]]). The picture saves on its
 * own, as every image section's does, and on a new item uploads with the
 * first *Speichern*.
 */
</script>

<template>
	<ResourceForm
		schema="news"
		:load="fetchNews"
		:save="saveNews"
		:remove="deleteNews"
		:list="{ name: 'content.news' }"
		:edit="(uuid) => ({ name: 'content.news.edit', params: { uuid } })"
		noun="News"
		:titles="{ create: 'News erfassen', edit: 'News bearbeiten' }"
		:deletion="{
			title: 'News löschen',
			text: 'Mit dieser Aktion wird die News gelöscht, mit ihrem Bild.',
			question: (form) => form.title,
		}"
		:stay="false"
	/>
</template>
