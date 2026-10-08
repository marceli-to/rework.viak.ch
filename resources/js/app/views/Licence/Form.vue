<script setup>
import { deleteLicence, fetchLicence, saveLicence } from '@/api/licences';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Lizenz erfassen* / *bearbeiten* ([[05-licences]]): the fields are
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
		noun="Lizenz"
		:titles="{ create: 'Lizenz erfassen', edit: 'Lizenz bearbeiten' }"
		:deletion="{
			title: 'Lizenz löschen',
			text: 'Mit dieser Aktion wird die Lizenz mit allen Varianten gelöscht.',
			question: (form) => form.title,
		}"
	/>
</template>
