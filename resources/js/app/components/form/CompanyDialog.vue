<script setup>
import { onMounted, reactive, ref } from 'vue';
import { saveCompany } from '@/api/companies';
import { fetchForm } from '@/api/forms';
import Button from '@/components/ui/Button.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Loading from '@/components/ui/Loading.vue';
import FormNode from './FormNode.vue';

/**
 * A company added from a Firmenkurs's picker, without leaving it
 * ([[16-firmenkurse]]), as [[TermDialog]] adds a software: the company's own
 * form ([[CompanySchema]]) in a lightbox, and what is created comes back in
 * `created` for the select to add and pick.
 */
const emit = defineEmits(['close', 'created']);

const schema = ref(null);
const form = reactive({});
const errors = ref({});
const saving = ref(false);

onMounted(async () => {
	schema.value = await fetchForm('company');
	Object.assign(form, schema.value.defaults);
});

async function save() {
	if (saving.value) return;
	saving.value = true;
	errors.value = {};
	try {
		emit('created', await saveCompany(null, form));
	} catch (problem) {
		errors.value = problem.errors ?? {};
	} finally {
		saving.value = false;
	}
}
</script>

<template>
	<Lightbox title="Firma hinzufügen" @close="emit('close')">
		<Loading v-if="!schema" />
		<form v-else @submit.prevent="save">
			<FormNode v-for="(field, index) in schema.fields" :key="field.name ?? index" :field="field" :model="form" :errors="errors" />
			<Button type="submit" class="w-full" :disabled="saving">Speichern</Button>
		</form>
	</Lightbox>
</template>
