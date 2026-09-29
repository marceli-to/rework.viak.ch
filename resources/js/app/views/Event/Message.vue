<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchEventPage, postMessage } from '@/api/events';
import { toast } from '@/composables/useToast';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import Editor from '@/components/form/Editor.vue';
import Field from '@/components/form/Field.vue';
import Loading from '@/components/ui/Loading.vue';

/**
 * *Nachricht erfassen* — legacy's `event-message-create`, a screen of its own
 * reached from the course date's page ([[07-dashboard]], step 7). The expert
 * portal's composer in the dashboard's fields: *Betreff*, *Nachricht* in the
 * editor, *Anhänge*, *Kopie an mich*. One multipart POST, so an abandoned
 * draft leaves no file behind ([[ExpertPortalController::storeMessage]]).
 * Sent to every live seat, which the page says before it is sent.
 */
const route = useRoute();
const router = useRouter();
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

function pick(event) {
	attachments.value = [...attachments.value, ...event.target.files];
	event.target.value = '';
}

// Laravel names each file's error `attachments.0`; shown under the list, once.
const fileError = () => Object.entries(errors.value).find(([key]) => key.startsWith('attachments'))?.[1]?.[0];

async function send() {
	sending.value = true;
	errors.value = {};

	try {
		await postMessage(route.params.uuid, { subject: subject.value, body: body.value, copyToMe: copyToMe.value, attachments: attachments.value });
		toast('Die Nachricht wurde gesendet.');
		router.push(back());
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
			<h1 class="font-bold text-teal">Nachricht erfassen</h1>
			<p class="mt-8">{{ page.course.number }} {{ page.course.title }}</p>
			<BackLink :to="back()" />
		</template>

		<form @submit.prevent="send">
			<Field v-model="subject" label="Betreff" required :error="errors.subject?.[0]" />
			<Editor v-model="body" label="Nachricht" required :error="errors.body?.[0]" class="mt-24" />

			<div class="mt-24">
				<div class="mb-4 text-md sm:text-lg lg:text-xl">Anhänge</div>
				<ul v-if="attachments.length" class="mb-12">
					<li v-for="(file, index) in attachments" :key="`${file.name}-${index}`" class="flex justify-between border-b border-gray-400 py-4">
						<span class="min-w-0 truncate">{{ file.name }}</span>
						<button type="button" class="ml-16 hover:text-teal" @click="attachments.splice(index, 1)">Entfernen</button>
					</li>
				</ul>
				<label class="cursor-pointer underline hover:text-teal">
					Dateien auswählen
					<input type="file" multiple class="sr-only" @change="pick" />
				</label>
				<div v-if="fileError()" class="mt-8 text-md text-danger lg:text-lg">{{ fileError() }}</div>
			</div>

			<Checkbox v-model="copyToMe" class="mt-24">Kopie an mich</Checkbox>

			<p class="mt-24">{{ page.participants.length === 1 ? 'Geht an den einen Teilnehmer dieses Kurses.' : `Geht an alle ${page.participants.length} Teilnehmer dieses Kurses.` }}</p>

			<div class="mt-24 sm:flex">
				<Button type="submit" :disabled="sending || !page.participants.length">{{ sending ? 'Wird gesendet …' : 'Senden' }}</Button>
			</div>
		</form>
	</ArticleText>
</template>
