<script setup>
import { useRoute } from 'vue-router';
import { deleteLicence, fetchLicence, saveLicence } from '@/api/licences';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Produkt erfassen* / *bearbeiten* ([[05-licences]]); first *Lizenz*, then
 * *Software*, then *Produkt* once the levels were named Software → Produkt →
 * Lizenz (Marcel, 2026-10-08): the fields are [[LicenceProductSchema]]; the
 * licences (variants) a list under them, each with its
 * own form ([[VariantSection]]).
 */
// The `+` under a software files the new product under it (`?software=`).
const route = useRoute();
const prefill = route.query.software ? { software: String(route.query.software) } : {};
</script>

<template>
	<ResourceForm
		schema="licence"
		:load="fetchLicence"
		:save="saveLicence"
		:remove="deleteLicence"
		:list="{ name: 'licences' }"
		:edit="(uuid) => ({ name: 'licence.edit', params: { uuid } })"
		:prefill="prefill"
		noun="Produkt"
		:titles="{ create: 'Produkt erfassen', edit: 'Produkt bearbeiten' }"
		:deletion="{
			title: 'Produkt löschen',
			text: 'Mit dieser Aktion wird das Produkt mit allen Lizenzen gelöscht.',
			question: (form) => form.title,
		}"
	/>
</template>
