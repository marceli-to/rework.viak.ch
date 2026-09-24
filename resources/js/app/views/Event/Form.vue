<script setup>
import { useRoute } from 'vue-router';
import { deleteEvent, fetchEvent, saveEvent } from '@/api/events';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Kursdatum erfassen* / *bearbeiten* ([[07-dashboard]], step 6): the fields
 * are [[EventSchema]]. A new one is created under the course in the path.
 *
 * Legacy's *Bestätigen*, *Schliessen* and *Absagen* boxes are not here yet:
 * each one mails the participants, and mail is chunk 10.
 */
const route = useRoute();
const save = (uuid, form) => saveEvent(uuid, form, route.params.course);

const day = (iso) => (iso ? iso.split('-').reverse().join('.') : '');
const bookings = (count) => (count === 1 ? 'eine Buchung' : `${count} Buchungen`);
</script>

<template>
	<ResourceForm
		schema="event"
		:load="fetchEvent"
		:save="save"
		:remove="deleteEvent"
		:list="{ name: 'courses' }"
		:edit="(uuid) => ({ name: 'event.edit', params: { uuid } })"
		noun="Kursdatum"
		:titles="{ create: 'Veranstaltung hinzufügen', edit: (meta) => `Veranstaltung für\n${meta.course.title}` }"
		:deletion="{
			title: 'Veranstaltung löschen',
			text: 'Mit dieser Aktion wird die Veranstaltung gelöscht.',
			question: (form, meta) => `${meta.course.number} ${meta.course.title}, ${day(form.dates[0]?.date)}`,
		}"
		:blocked="(meta) => (meta.bookings ? `Diese Veranstaltung kann nicht gelöscht werden, da ${bookings(meta.bookings)} vorhanden sind.` : null)"
		:locked="(meta) => meta.is_past"
		:stay="false"
	/>
</template>
