<script setup>
import { deleteSoftware, fetchSoftware, saveSoftware } from '@/api/software';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Software erfassen* / *bearbeiten* ([[05-licences]]): its page, drawn as a
 * course page is, so the form is the course form's ([[SoftwareSchema]]). The
 * pencil on a software's collapsible on the *Software* screen opens it.
 */
const counted = (count, one, many) => `${count} ${count === 1 ? one : many}`;
const blocked = (meta) => {
	const uses = [
		...(meta.courses_count ? [counted(meta.courses_count, 'Kurs', 'Kursen')] : []),
		...(meta.products_count ? [counted(meta.products_count, 'Produkt', 'Produkten')] : []),
	];
	return uses.length ? `Verwendet von ${uses.join(' und ')}, kann darum nicht gelöscht werden.` : null;
};
</script>

<template>
	<ResourceForm
		schema="software"
		:load="fetchSoftware"
		:save="saveSoftware"
		:remove="deleteSoftware"
		:list="{ name: 'licences' }"
		:edit="(uuid) => ({ name: 'licence.software.edit', params: { uuid } })"
		noun="Software"
		:titles="{ create: 'Software erfassen', edit: 'Software bearbeiten' }"
		:deletion="{
			title: 'Software löschen',
			text: 'Mit dieser Aktion wird die Software gelöscht.',
			question: (form) => form.title,
		}"
		:blocked="blocked"
	/>
</template>
