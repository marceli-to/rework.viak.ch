<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { fetchSoftware, saveSoftwarePage } from '@/api/software';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * One of a software's pages, a form of its own (Marcel, 2026-11-17,
 * [[14-angebote]] *The form, by page*): opened from its row under the
 * software in the *Software* list, back to that list with the software open.
 * Edited only, never created or deleted: the page belongs to the software.
 */
const PAGES = {
	angebot: { schema: 'software-angebot', label: 'Seite «Angebot»', preview: 'live_url' },
	'was-ist': { schema: 'software-was-ist', label: 'Seite «Was ist …?»', preview: 'about_url' },
	lizenzen: { schema: 'software-lizenzen', label: 'Seite «Lizenzen»', preview: 'licences_url' },
};

const route = useRoute();
const page = computed(() => PAGES[route.params.page]);
const heading = (meta) => (meta.title ? `${meta.title}\n${page.value.label}` : page.value.label);
</script>

<template>
	<!-- Keyed on the path: from one page's form to another's loads afresh. -->
	<ResourceForm
		:key="route.fullPath"
		:schema="page.schema"
		:load="fetchSoftware"
		:save="saveSoftwarePage(route.params.page)"
		:list="{ name: 'licences', query: { software: route.params.uuid } }"
		:edit="(uuid) => ({ name: 'licence.software.page', params: { uuid, page: route.params.page } })"
		noun="Seite"
		:titles="{ create: page.label, edit: heading }"
		:preview="page.preview"
	/>
</template>
