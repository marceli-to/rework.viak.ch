<script setup>
import { deleteTestimonial, fetchTestimonial, saveTestimonial } from '@/api/testimonials';
import ResourceForm from '@/components/form/ResourceForm.vue';

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
		:deletion="{ title: 'Testimonial löschen', text: 'Mit dieser Aktion wird das Testimonial gelöscht.', question: (form) => form.name }"
		:stay="false"
	/>
</template>
