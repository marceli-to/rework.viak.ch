<script setup>
import { deleteDiscountCode, fetchDiscountCode, saveDiscountCode } from '@/api/discountCodes';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Rabatt-Code erfassen* / *bearbeiten* — legacy's `views/discount/Form.vue`
 * ([[07-dashboard]], step 6): the fields are [[DiscountCodeSchema]]. The code
 * is generated when the form opens and shown, not typed, as legacy has it.
 */
const used = (meta) => {
	const times = meta.times_used === 1 ? 'einmal' : `${meta.times_used} Mal`;
	return meta.times_used ? `Bisher ${times} eingelöst.` : 'Noch nie eingelöst.';
};
</script>

<template>
	<ResourceForm
		schema="discount-code"
		:load="fetchDiscountCode"
		:save="saveDiscountCode"
		:remove="deleteDiscountCode"
		:list="{ name: 'discount-codes' }"
		:edit="(uuid) => ({ name: 'discount-code.edit', params: { uuid } })"
		noun="Rabatt-Code"
		:titles="{ create: 'Rabatt-Code erfassen', edit: 'Rabatt-Code bearbeiten' }"
		:deletion="{
			title: 'Rabatt-Code löschen',
			text: 'Mit dieser Aktion wird der Rabatt-Code gelöscht. Buchungen, die ihn verwendet haben, behalten ihren Rabatt.',
			question: (form) => form.code,
		}"
		:note="used"
		:stay="false"
	/>
</template>
