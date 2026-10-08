<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { deleteVariant, fetchVariant, saveVariant } from '@/api/licences';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Variante erfassen* / *bearbeiten* ([[05-licences]]): the fields are
 * [[LicenceVariantSchema]]. Both paths carry the product, so the way back and
 * a new variant's home are known before anything loads.
 */
const route = useRoute();
const product = computed(() => route.params.product);
const save = (uuid, form) => saveVariant(uuid, form, product.value);
</script>

<template>
	<ResourceForm
		schema="licence-variant"
		:load="fetchVariant"
		:save="save"
		:remove="deleteVariant"
		:list="{ name: 'licence.edit', params: { uuid: product } }"
		:edit="(uuid) => ({ name: 'licence.variant.edit', params: { product, uuid } })"
		noun="Variante"
		:titles="{ create: 'Variante erfassen', edit: (meta) => `Variante für\n${meta.product?.title ?? ''}` }"
		:deletion="{
			title: 'Variante löschen',
			text: 'Mit dieser Aktion wird die Variante gelöscht.',
			question: (form) => form.title,
		}"
		:stay="false"
	/>
</template>
