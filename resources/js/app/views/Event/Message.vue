<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchEventPage, postMessage } from '@/api/events';
import { toast } from '@/composables/useToast';
import { goBack } from '@/router';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import DropBox from '@/components/form/DropBox.vue';
import IconCross from '@/components/icons/Cross.vue';
import Editor from '@/components/form/Editor.vue';
import Field from '@/components/form/Field.vue';
import Loading from '@/components/ui/Loading.vue';

/**
 * *Nachricht erstellen* — legacy's `event-message-create`, a screen of its own
 * reached from the event page ([[07-dashboard]], step 7). **The expert
 * portal's composer, class for class** (`site/expert/message.blade.php`,
 * Marcel, 2026-09-29: match legacy): legacy's sentence in the aside,
 * *Betreff*, *Nachricht*, *Anhänge (max. 32 MB)* with a rule under the drop
 * box, *Kopie der Nachricht an mich* with a rule under it, and a full-width
 * *Senden*. One multipart POST, so an abandoned draft leaves no file behind
 * ([[ExpertPortalController::storeMessage]]). Sent to every live seat.
 */
const route = useRoute();
const page = ref(null);
const error = ref(null);

const subject = ref('');
const body = ref('');
const copyToMe = ref(false);
const attachments = ref([]);
const errors = ref({});
const sending = ref(false);

const back = () => ({ name: 'event.show', params: { uuid: route.params.uuid } });

onMounted(async () => {
	try {
		page.value = await fetchEventPage(route.params.uuid);
	} catch (problem) {
		error.value = problem.message;
	}
});

const size = (bytes) => (bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

// Laravel names each file's error `attachments.0`; shown under the list, once.
const fileError = () => Object.entries(errors.value).find(([key]) => key.startsWith('attachments'))?.[1]?.[0];

async function send() {
	sending.value = true;
	errors.value = {};

	try {
		await postMessage(route.params.uuid, { subject: subject.value, body: body.value, copyToMe: copyToMe.value, attachments: attachments.value });
		toast('Die Nachricht wurde gesendet.');
		goBack(back());
	} catch (problem) {
		errors.value = problem.errors;
		if (!Object.keys(problem.errors).length) toast(problem.message, 'error');
	} finally {
		sending.value = false;
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<ArticleText v-else>
		<template #aside>
			<h1 class="font-bold text-teal">Nachricht erstellen</h1>
			<p class="text-md sm:mt-12 sm:text-lg lg:text-xl">Sende eine Nachricht an alle Teilnehmer dieses Kurses.</p>
			<BackLink :to="back()" />
		</template>

		<form @submit.prevent="send">
			<Field v-model="subject" label="Betreff" required :error="errors.subject?.[0]" />
			<Editor v-model="body" label="Nachricht" required :error="errors.body?.[0]" class="mt-24" />

			<!-- `x-form.file-input` with `rule`: the drop box, its limits, the chosen
			     files, and a black rule under the group. Nothing leaves before *Senden*. -->
			<div class="relative mt-24 mb-16 border-b border-black pb-16 sm:pb-32 lg:mb-32">
				<div class="mb-4 text-md sm:text-lg lg:text-xl">Anhänge (max. 32 MB)</div>
				<DropBox class="mt-8 sm:mt-16" :accept="page.uploads.accept" :restrictions="page.uploads.restrictions" @files="(files) => attachments.push(...files)" />
				<p v-if="fileError()" class="pt-8 text-md text-danger lg:text-lg">{{ fileError() }}</p>
				<ul v-if="attachments.length" class="mt-16 sm:mt-24">
					<li v-for="(file, index) in attachments" :key="`${file.name}-${file.size}-${file.lastModified}`" class="flex items-center justify-between gap-16 border-t border-black py-8 text-xs sm:text-md lg:text-lg">
						<span class="min-w-0 truncate">{{ file.name }}</span>
						<span class="flex shrink-0 items-center gap-16">
							<span class="text-gray-400">{{ size(file.size) }}</span>
							<button type="button" class="transition-colors hover:text-teal" :aria-label="`Entfernen: ${file.name}`" @click="attachments.splice(index, 1)"><IconCross size="sm" /></button>
						</span>
					</li>
				</ul>
			</div>

			<!-- `.line-after`, as on the portal: the checkbox over a black rule. -->
			<div class="mb-16 border-b border-black pb-22 sm:pb-26 lg:mb-32 lg:pb-36">
				<Checkbox v-model="copyToMe">Kopie der Nachricht an mich</Checkbox>
			</div>

			<div class="mb-16 lg:mb-32">
				<Button type="submit" class="w-full" :class="{ 'pointer-events-none opacity-60': sending || !page.participants.length }">{{ sending ? 'Wird gesendet …' : 'Senden' }}</Button>
			</div>
		</form>
	</ArticleText>
</template>
