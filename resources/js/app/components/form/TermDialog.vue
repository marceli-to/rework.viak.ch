<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { settingsOf } from '@/api/settings';
import Button from '@/components/ui/Button.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Field from './Field.vue';
import { KINDS } from '@/views/Setting/kinds';

/**
 * A settings term created from a form that picks one, without leaving it
 * (Marcel, 2026-10-08): *Software-Gruppe* and *Hersteller* on the licence
 * form. The same call and the same rules as the term's own form
 * ([[SettingController]], [[TermSchema]]); what is created comes back in
 * `created`, for the select to add and pick.
 */
const props = defineProps({
	kind: { type: String, required: true },
});

const emit = defineEmits(['close', 'created']);

const title = ref('');
const error = ref(null);
const saving = ref(false);
const box = ref(null);

async function save() {
	if (saving.value) return;
	saving.value = true;
	error.value = null;
	try {
		emit('created', await settingsOf(props.kind).save(null, { title: title.value }));
	} catch (problem) {
		error.value = problem.errors.title?.[0] ?? problem.message;
	} finally {
		saving.value = false;
	}
}

onMounted(async () => {
	await nextTick();
	box.value?.querySelector('input')?.focus();
});
</script>

<template>
	<Lightbox :title="`${KINDS[kind].noun} hinzufügen`" narrow @close="emit('close')">
		<form ref="box" @submit.prevent="save">
			<Field v-model="title" label="Bezeichnung" required :error="error" />
			<Button type="submit" class="w-full">Speichern</Button>
		</form>
	</Lightbox>
</template>
