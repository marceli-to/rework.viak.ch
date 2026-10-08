<script setup>
import { deleteLicence, fetchLicence, saveLicence } from '@/api/licences';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Software erfassen* / *bearbeiten* ([[05-licences]]), named as the menu
 * item is (Marcel, 2026-10-08; first *Lizenz*): the fields are
 * [[LicenceProductSchema]]; the variants a list under them, each with its
 * own form ([[VariantSection]]).
 */
</script>

<template>
	<ResourceForm
		schema="licence"
		:load="fetchLicence"
		:save="saveLicence"
		:remove="deleteLicence"
		:list="{ name: 'licences' }"
		:edit="(uuid) => ({ name: 'licence.edit', params: { uuid } })"
		noun="Software"
		:titles="{ create: 'Software erfassen', edit: 'Software bearbeiten' }"
		:deletion="{
			title: 'Software löschen',
			text: 'Mit dieser Aktion wird die Software mit allen Varianten gelöscht.',
			question: (form) => form.title,
		}"
	/>
</template>
