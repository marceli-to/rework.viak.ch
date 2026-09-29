<script setup>
import { fetchProfile, saveProfile } from '@/api/profile';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Mein Profil* — legacy's `views/admin/Index.vue` ([[07-dashboard]], step
 * 6): the fields are [[ProfileSchema]]. One record, the signed-in admin, so
 * the form stays where it is after saving. Legacy's read-only view with a
 * pencil to open the form is left out: the form is the view.
 */
const unconfirmed = (meta) => (meta.email_verified === false ? 'Die E-Mail-Adresse ist noch nicht bestätigt. Der Link dazu ist unterwegs.' : null);
</script>

<template>
	<ResourceForm
		schema="profile"
		:load="fetchProfile"
		:save="saveProfile"
		:list="{ name: 'courses' }"
		:edit="() => ({ name: 'profile' })"
		noun="Profil"
		:titles="{ create: 'Mein Profil', edit: 'Mein Profil' }"
		:note="unconfirmed"
		singleton
	/>
</template>
