<script setup>
import { deleteTestimonial, fetchTestimonial, saveTestimonial } from '@/api/testimonials';
import ResourceForm from '@/components/form/ResourceForm.vue';

/** Where it stands — placed by the pages' pickers, read here. */
const used = (meta) => meta.placements.map((place) => place.label).join(', ');

/** Enough of the quote to know which one it is, cut at a word. */
const excerpt = (text, max = 160) => (text.length > max ? `${text.slice(0, max).replace(/\s+\S*$/, '')} …` : text);

/** What deleting asks about: the quote over its author, and where it goes from. */
const question = (form, meta) =>
	[
		`„${excerpt(form.quote)}“`,
		form.context ? `${form.name} (${form.context})` : form.name,
		meta.placements?.length ? `Wird auch entfernt von: ${used(meta)}` : null,
	]
		.filter(Boolean)
		.join('\n');

/**
 * *Testimonial erfassen* / *bearbeiten* ([[07-dashboard]], step 4), on the field
 * kit since step 5: the fields are [[TestimonialSchema]]. One save button:
 * a testimonial is three lines, so there is nothing to keep editing
 * (Marcel, 2026-09-24).
 */
</script>

<template>
	<ResourceForm
		schema="testimonial"
		:load="fetchTestimonial"
		:save="saveTestimonial"
		:remove="deleteTestimonial"
		:list="{ name: 'content.testimonials' }"
		:edit="(uuid) => ({ name: 'content.testimonial.edit', params: { uuid } })"
		noun="Testimonial"
		:titles="{ create: 'Testimonial erfassen', edit: 'Testimonial bearbeiten' }"
		:deletion="{
			title: 'Testimonial löschen',
			text: 'Mit dieser Aktion wird das Testimonial gelöscht.',
			question,
		}"
		:note="(meta) => (meta.placements?.length ? `Verwendet auf: ${used(meta)}` : 'Noch auf keiner Seite verwendet.')"
		:stay="false"
	/>
</template>
