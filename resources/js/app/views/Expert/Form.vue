<script setup>
import { deleteExpert, fetchExpert, saveExpert } from '@/api/experts';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Experte hinzufügen* / *bearbeiten* — legacy's `views/expert/Form.vue`
 * ([[07-dashboard]], step 6): the fields are [[ExpertSchema]].
 *
 * **Deleting is for someone nothing points at** (#16). Anyone who has taught,
 * booked or been invoiced stays, and is switched off instead. A new expert
 * is mailed *Dein VIAK-Zugang* to set their password ([[AccountInvitation]]).
 */
const blocked = (meta) => {
	if (meta.is_self) return 'Du kannst dein eigenes Konto nicht löschen.';
	if (meta.has_history) return 'Diese Person hat Kursdaten, Buchungen oder Rechnungen und kann nicht gelöscht werden. Setze sie stattdessen auf nicht aktiv, oder nimm ihr die Rolle Experte.';
	return null;
};
</script>

<template>
	<ResourceForm
		schema="expert"
		:load="fetchExpert"
		:save="saveExpert"
		:remove="deleteExpert"
		:list="{ name: 'experts' }"
		:edit="(uuid) => ({ name: 'expert.edit', params: { uuid } })"
		noun="Experte"
		:titles="{ create: 'Experte hinzufügen', edit: 'Experte bearbeiten' }"
		:deletion="{
			title: 'Experte löschen',
			text: 'Mit dieser Aktion wird das Konto gelöscht, mit den Profilbildern.',
			question: (form) => `${form.first_name} ${form.last_name}, ${form.email}`,
		}"
		:blocked="blocked"
		:note="(meta) => (meta.email_verified ? null : 'Die E-Mail-Adresse ist noch nicht bestätigt.')"
	/>
</template>
