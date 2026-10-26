<script setup>
import { deleteCompany, fetchCompany, saveCompany } from '@/api/companies';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Firma erfassen* / *bearbeiten* ([[16-firmenkurse]]): the fields are
 * [[CompanySchema]]. A change reaches the next invoice; an issued one keeps
 * the address it was sent to. Refused deleting while it has a Firmenkurs or
 * an invoice: its id is a customer number in the books.
 */
const used = (meta) => (meta.courses || meta.invoices ? 'Die Firma hat Firmenkurse oder Rechnungen und kann nicht gelöscht werden.' : null);
</script>

<template>
	<ResourceForm
		schema="company"
		:load="fetchCompany"
		:save="saveCompany"
		:remove="deleteCompany"
		:list="{ name: 'company-courses', query: { liste: 'firmen' } }"
		:edit="(uuid) => ({ name: 'company.edit', params: { uuid } })"
		noun="Firma"
		:titles="{ create: 'Firma erfassen', edit: 'Firma bearbeiten' }"
		:deletion="{
			title: 'Firma löschen',
			text: 'Mit dieser Aktion wird die Firma gelöscht.',
			question: (form) => form.company,
		}"
		:blocked="used"
		:stay="false"
	/>
</template>
