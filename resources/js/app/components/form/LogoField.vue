<script setup>
import { ref } from 'vue';
import client from '@/api/client';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import DropBox from './DropBox.vue';

/**
 * *Logo* in the software form's *Einstellungen* (Oliver, 2026-11-17,
 * [[14-angebote]] *Logos*): the Angebot's logo in every list and overview of
 * the site. **Saved at once**, as the images are, not with *Speichern*: so
 * only on a saved Angebot. One file; a new one replaces it. *Entfernen*
 * under it: an Angebot without a logo shows none, nothing stands in.
 *
 * The drop box is the images' ([[DropBox]]), the logo above it on a grey
 * ground so a white one is seen.
 */
const props = defineProps({
	record: { type: String, default: null },
	meta: { type: Object, default: () => ({}) },
	// Named by the schema as every custom part's is; the logo has one owner.
	owner: { type: String, default: 'software' },
});

const url = ref(props.meta.logo_url ?? null);
const busy = ref(false);

async function upload([file]) {
	const body = new FormData();
	body.append('file', file);
	busy.value = true;
	try {
		const saved = await client.post(`/admin/software/${props.record}/logo`, body).then((r) => r.data.data);
		url.value = saved.logo_url;
		toast('Logo gespeichert');
	} catch (problem) {
		toast(problem.errors?.file?.[0] ?? problem.message, 'error');
	} finally {
		busy.value = false;
	}
}

async function remove() {
	if (!(await confirm('Logo entfernen?', 'Das Angebot zeigt dann kein Logo.'))) return;
	busy.value = true;
	try {
		await client.delete(`/admin/software/${props.record}/logo`);
		url.value = null;
		toast('Logo entfernt');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<div class="mb-16 lg:mb-32">
		<span class="mb-4 block text-md sm:text-lg lg:text-xl">Logo</span>

		<p v-if="!record" class="text-md text-gray-600 lg:text-lg">Nach dem ersten Speichern.</p>

		<template v-else>
			<div v-if="url" class="mb-16 flex items-end justify-between gap-16">
				<div class="bg-gray-200 p-8"><img :src="url" alt="" class="block size-80 object-contain" /></div>
				<button type="button" class="transition-colors hover:text-teal sm:text-lg lg:text-xl" :disabled="busy" @click="remove">Entfernen</button>
			</div>
			<DropBox accept="image/png,image/svg+xml,image/webp,image/jpeg" restrictions="PNG, SVG, WebP | max. 2 MB, quadratisch" :multiple="false" @files="upload" />
		</template>
	</div>
</template>
