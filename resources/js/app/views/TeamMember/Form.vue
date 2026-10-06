<script setup>
import { deleteTeamMember, fetchTeamMember, saveTeamMember } from '@/api/teamMembers';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Teammitglied erfassen* / *bearbeiten* ([[07-dashboard]]): the fields are
 * [[TeamMemberSchema]]. The portrait saves on its own, as every image section's
 * does, and on a new person it uploads with the first *Speichern*.
 */
</script>

<template>
	<ResourceForm
		schema="team-member"
		:load="fetchTeamMember"
		:save="saveTeamMember"
		:remove="deleteTeamMember"
		:list="{ name: 'content.team' }"
		:edit="(uuid) => ({ name: 'content.team-member.edit', params: { uuid } })"
		noun="Teammitglied"
		:titles="{ create: 'Teammitglied erfassen', edit: 'Teammitglied bearbeiten' }"
		:deletion="{
			title: 'Teammitglied löschen',
			text: 'Mit dieser Aktion wird das Teammitglied gelöscht, mit seinem Bild.',
			question: (form) => (form.role ? `${form.name}, ${form.role}` : form.name),
		}"
		:stay="false"
	/>
</template>
