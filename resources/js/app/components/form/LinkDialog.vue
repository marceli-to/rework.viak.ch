<script setup>
import { nextTick, onMounted, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Field from './Field.vue';

/**
 * The editor's link, in a lightbox (Marcel, 2026-10-08), where it was a bar
 * over the text ([[Editor]]). *Adresse*, and *Text* when nothing is
 * selected to put the link on; empty, the address is the text. *Entfernen*
 * on a link that is there already. The editor applies what comes back.
 */
const props = defineProps({
	href: { type: String, default: '' },
	// Nothing selected and not inside a link: the link needs words of its own.
	needsText: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'apply', 'remove']);

const url = ref(props.href);
const text = ref('');
const error = ref(null);
const box = ref(null);

function apply() {
	if (url.value.trim() === '') {
		error.value = 'Bitte eine Adresse eingeben.';
		return;
	}
	emit('apply', { url: url.value.trim(), text: text.value.trim() });
}

onMounted(async () => {
	await nextTick();
	box.value?.querySelector('input')?.focus();
});
</script>

<template>
	<Lightbox :title="href ? 'Link bearbeiten' : 'Link einfügen'" narrow @close="emit('close')">
		<form ref="box" @submit.prevent="apply">
			<Field v-model="url" label="Adresse" required hint="www.beispiel.ch oder name@beispiel.ch" :error="error" />
			<Field v-if="needsText" v-model="text" label="Text" hint="Leer lassen, um die Adresse als Text zu zeigen." />
			<div class="flex flex-col gap-12 sm:flex-row">
				<Button type="submit" class="sm:flex-1">Übernehmen</Button>
				<Button v-if="href" variant="secondary" class="sm:flex-1" @click="emit('remove')">Entfernen</Button>
			</div>
		</form>
	</Lightbox>
</template>
