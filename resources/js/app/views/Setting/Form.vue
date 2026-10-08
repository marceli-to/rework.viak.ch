<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { settingsOf } from '@/api/settings';
import ResourceForm from '@/components/form/ResourceForm.vue';
import { KINDS, usage } from './kinds';

/**
 * A term or a place — legacy's five settings forms as two schemas
 * ([[TermSchema]], [[LocationSchema]]), picked by the kind in the path.
 * Deleting is refused while something uses it ([[SettingController]]).
 */
const route = useRoute();
const key = computed(() => route.params.kind);
const kind = computed(() => KINDS[key.value]);
const calls = computed(() => settingsOf(key.value));
const name = (form) => form.title ?? form.description;
</script>

<template>
	<ResourceForm
		v-if="kind"
		:key="key"
		:schema="kind.schema"
		:load="calls.load"
		:save="calls.save"
		:remove="calls.remove"
		:list="{ name: 'settings', query: { liste: key } }"
		:edit="(uuid) => ({ name: 'setting.edit', params: { kind: key, uuid } })"
		:noun="kind.noun"
		:titles="{ create: `${kind.noun} hinzufügen`, edit: `${kind.noun} bearbeiten` }"
		:deletion="{
			title: `${kind.noun} löschen`,
			text: `Mit dieser Aktion wird ${key === 'locations' ? 'der Ort' : 'der Eintrag'} gelöscht.`,
			question: (form) => name(form),
		}"
		:blocked="(meta) => (meta.usage ? `Verwendet von ${usage(key, meta).join(' und ')}, kann darum nicht gelöscht werden.` : null)"
		:stay="false"
	/>
	<p v-else class="text-danger">Diese Liste gibt es nicht.</p>
</template>
