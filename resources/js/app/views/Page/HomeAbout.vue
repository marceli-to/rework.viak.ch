<script setup>
import { fetchPageContent, savePageContent } from '@/api/pages';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Seiteninhalte → Startseite: Über uns* ([[04-content]]): the homepage's
 * About teaser, its heading, text and image ([[HomeAboutSchema]]). One record
 * with no list, edited as the profile is (`singleton`); the image section
 * saves on its own, as everywhere.
 */
</script>

<template>
	<ResourceForm
		schema="home-about"
		:load="() => fetchPageContent('home-about')"
		:save="(_, form) => savePageContent('home-about', form)"
		:list="{ name: 'content.home' }"
		:edit="() => ({ name: 'content.home-about' })"
		noun="Über uns"
		:titles="{ create: 'Startseite: Über uns', edit: 'Startseite: Über uns' }"
		:stay="false"
		singleton
	/>
</template>
