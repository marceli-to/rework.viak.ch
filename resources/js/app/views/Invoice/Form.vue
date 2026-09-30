<script setup>
import { fetchInvoice, saveInvoice } from '@/api/invoices';
import { shortDate } from '@/support/format';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Rechnung bearbeiten* — legacy's `views/backoffice/invoice/Edit.vue`
 * ([[07-dashboard]], step 7): the invoice address, as fields
 * ([[InvoiceSchema]]). Saving makes the PDF again.
 *
 * Legacy showed the number and the student as greyed-out inputs; here they
 * are the note. Where a ported invoice carries an address that cannot be
 * split into fields, the note says what it prints today, since the fields
 * start from the student's own ([[InvoiceFormResource]]). A paid or cancelled
 * invoice opens locked: the list offers no pencil for one, but a link can.
 */
const note = (meta) =>
	[
		`Rechnung ${meta.number} vom ${shortDate(meta.date)} über CHF ${Number(meta.grand_total).toFixed(2)}${meta.customer ? `, ${meta.customer}` : ''}.`,
		meta.printed?.length ? `Heute steht darauf: ${meta.printed.join(', ')}. Die Felder sind aus dem Profil vorausgefüllt.` : null,
		meta.editable ? 'Beim Speichern wird das PDF neu erstellt.' : 'Bezahlt oder storniert: die Adresse wird nicht mehr geändert.',
	]
		.filter(Boolean)
		.join(' ');
</script>

<template>
	<ResourceForm
		schema="invoice"
		:load="fetchInvoice"
		:save="saveInvoice"
		:list="{ name: 'backoffice.invoices' }"
		:edit="(uuid) => ({ name: 'backoffice.invoice.edit', params: { uuid } })"
		noun="Rechnung"
		:titles="{ create: 'Rechnung', edit: 'Rechnung bearbeiten' }"
		:note="note"
		:locked="(meta) => !meta.editable"
		:stay="false"
	/>
</template>
