<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { bookStudent, downloadParticipants, fetchEventPage, removeFile, uploadFiles } from '@/api/events';
import { fetchStudents } from '@/api/students';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { returnTo } from '@/router';
import { shortDate } from '@/support/format';
import ArticleText from '@/components/layout/ArticleText.vue';
import AttendanceBadge from '@/components/course/AttendanceBadge.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DropBox from '@/components/form/DropBox.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EventRow from '@/components/course/EventRow.vue';
import IconDownload from '@/components/icons/Download.vue';
import IconPlus from '@/components/icons/Plus.vue';
import IconTrash from '@/components/icons/Trash.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Loading from '@/components/ui/Loading.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * A course date's own page — legacy's `course/event/Show.vue` ([[07-dashboard]],
 * step 7, [[EventPageController]]):
 *
 * - *Informationen*, the date's row as on *Kurse*.
 * - *Teilnehmer*, each with its attendance as a badge ([[AttendanceBadge]]),
 *   *Teilnahme offen* until the date is closed. **Attendance is asked when the date is closed**, in the edit
 *   form's lightbox, not ticked here (Marcel, 2026-09-29): legacy's tick and
 *   *Teilgenommen?* are gone. Under the list,
 *   *Teilnehmer hinzufügen* and *Teilnehmerliste* with the download icon.
 * - *Nachrichten*, legacy's messages module: a row per note that opens it in
 *   a lightbox, and the plus to *Nachricht erfassen*. Only with participants,
 *   as legacy.
 * - *Kurs-Dokumente*: the course materials, a download each and a bin, and
 *   the expert portal's drop box under them, which uploads what is dropped
 *   straight away (legacy had a screen for it).
 *
 * *Teilnehmer hinzufügen* searches as *Studenten* does, on the server, and
 * books with one click; legacy's select box under a search field was two.
 */
const route = useRoute();
const page = ref(null);
const error = ref(null);

const state = computed(() => page.value?.event.state);
const closed = computed(() => state.value === 'closed');
const cancelled = computed(() => state.value === 'cancelled');
const open = computed(() => !closed.value && !cancelled.value);

async function load() {
	try {
		page.value = await fetchEventPage(route.params.uuid);
	} catch (problem) {
		error.value = problem.message;
	}
}

onMounted(load);

// Teilnehmer hinzufügen
const adding = ref(false);
const search = ref('');
const found = ref([]);
const searching = ref(false);
const booking = ref(null);
const booked = computed(() => new Set(page.value?.participants.map((participant) => participant.student)));

watch(search, async (value) => {
	if (!value.trim()) {
		found.value = [];
		return;
	}
	searching.value = true;
	try {
		const reply = await fetchStudents({ search: value });
		if (value === search.value) found.value = reply.data;
	} finally {
		searching.value = false;
	}
});

function closeAdding() {
	adding.value = false;
	search.value = '';
}

async function add(student) {
	booking.value = student.uuid;
	try {
		await bookStudent(page.value.event.uuid, student.uuid);
		toast(`${student.name} wurde hinzugefügt.`);
		closeAdding();
		await load();
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		booking.value = null;
	}
}

const downloading = ref(false);

async function pdf() {
	downloading.value = true;
	try {
		await downloadParticipants(page.value.event.uuid);
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		downloading.value = false;
	}
}

// Nachrichten
const reading = ref(null);
const preview = (html) => {
	const text = new DOMParser().parseFromString(html, 'text/html').body.textContent.trim();
	return text.length > 35 ? `${text.slice(0, 35)}…` : text;
};

// Kurs-Dokumente
const uploading = ref(false);

async function upload(files) {

	uploading.value = true;
	try {
		await uploadFiles(page.value.event.uuid, files);
		toast(files.length === 1 ? 'Das Dokument wurde hochgeladen.' : 'Die Dokumente wurden hochgeladen.');
		await load();
	} catch (problem) {
		toast(Object.values(problem.errors)[0]?.[0] ?? problem.message, 'error');
	} finally {
		uploading.value = false;
	}
}

async function remove(file) {
	if (!(await confirm('Bitte Löschen bestätigen!', `${file.name} wird entfernt.`))) return;

	try {
		await removeFile(page.value.event.uuid, file.uuid);
		page.value.files = page.value.files.filter((item) => item.uuid !== file.uuid);
		toast('Das Dokument wurde entfernt.');
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

const size = (bytes) => (bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<section v-else>
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal">{{ page.course.number }} {{ page.course.title }}</h1>
				<BackLink :to="returnTo({ name: 'courses' })" />
			</template>
		</ArticleText>

		<!-- The aside has no column beside it here, so the lists keep clear of *Zurück*. -->
		<div class="mt-32 sm:mt-48">
			<Collapsible expanded>
				<template #title>Informationen</template>
				<EventRow :event="page.event" :details="false" />
			</Collapsible>

			<Collapsible expanded>
				<template #title>Teilnehmer<Badge v-if="page.participants.length" variant="solid" class="ml-12">{{ page.participants.length }}</Badge></template>

				<template v-if="page.participants.length">
					<!-- Legacy's stacked row, 2/2/2/3/1/2 of twelve. -->
					<article
						v-for="participant in page.participants"
						:key="participant.uuid"
						class="mt-16 grid grid-cols-12 gap-x-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:gap-x-40 lg:text-xl"
					>
						<div class="col-span-12 sm:col-span-2">
							<RouterLink :to="{ name: 'student.show', params: { uuid: participant.student } }" class="hover:text-teal">{{ participant.name }}</RouterLink>
						</div>
						<div class="col-span-6 sm:col-span-2">{{ participant.city }}</div>
						<div class="col-span-6 sm:col-span-2">{{ participant.company }}</div>
						<div class="col-span-12 min-w-0 truncate sm:col-span-3">
							<a :href="`mailto:${participant.email}`" class="hover:text-teal">{{ participant.email }}</a>
						</div>
						<div class="col-span-6 sm:col-span-1">{{ participant.has_rental ? 'Mietcomputer' : '' }}</div>
						<div class="col-span-6 flex items-start justify-end sm:col-span-2">
							<!-- Recorded when the date was closed ([[EventPageController::close]]). -->
							<AttendanceBadge :participated="participant.participated" :closed="closed" />
						</div>
					</article>
				</template>
				<p v-else class="mt-16 sm:mt-32">Es sind keine Anmeldungen für diesen Kurs vorhanden.</p>

				<!-- Legacy's pair under the list: the plus with its label, the arrow with its. -->
				<div v-if="!cancelled" class="mt-24 flex justify-between sm:mt-48">
					<button v-if="open" type="button" class="flex items-center gap-12 hover:text-teal" @click="adding = true">
						<span>Teilnehmer hinzufügen</span>
						<IconPlus size="md" />
					</button>
					<span v-else />
					<!-- The invoices' download icon beside the label, as the plus stands beside *Teilnehmer hinzufügen*. -->
					<button v-if="page.participants.length" type="button" class="flex items-center gap-12 hover:text-teal" :disabled="downloading" @click="pdf">
						<span>{{ downloading ? 'Wird erstellt …' : 'Teilnehmerliste' }}</span>
						<IconDownload class="size-18" />
					</button>
				</div>
			</Collapsible>

			<Collapsible v-if="page.participants.length">
				<template #title>Nachrichten<Badge v-if="page.messages.length" variant="solid" class="ml-12">{{ page.messages.length }}</Badge></template>

				<!-- The portals' message row (`row/message.blade.php`): date, sender, preview, *Anzeigen*. -->
				<article v-for="message in page.messages" :key="message.uuid" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="mb-8 sm:hidden">{{ shortDate(message.created_at) }}, {{ message.author }}</div>
						<div class="mb-4 max-sm:hidden sm:col-span-2">{{ shortDate(message.created_at) }}</div>
						<div class="mb-4 max-sm:hidden sm:col-span-3">{{ message.author }}</div>
						<div class="sm:col-span-4 md:col-span-5"><strong class="font-bold">{{ message.subject }}</strong><br />{{ preview(message.body) }}</div>
						<div class="mt-24 sm:col-span-3 sm:mt-0 md:col-span-2">
							<Button variant="secondary" class="w-full" @click="reading = message">Anzeigen</Button>
						</div>
					</div>
				</article>
				<p v-if="!page.messages.length" class="mt-16 sm:mt-32">Es sind noch keine Nachrichten vorhanden.</p>

				<div class="mt-24 flex">
					<RouterLink :to="{ name: 'event.message', params: { uuid: page.event.uuid } }" title="Nachricht erfassen" class="block hover:text-teal">
						<IconPlus size="lg" class="block" />
					</RouterLink>
				</div>
			</Collapsible>

			<Collapsible>
				<template #title>Kurs-Dokumente<Badge v-if="page.files.length" variant="solid" class="ml-12">{{ page.files.length }}</Badge></template>

				<article v-for="file in page.files" :key="file.uuid" class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="flex items-start justify-between gap-16">
						<a :href="file.url" target="_blank" class="min-w-0 truncate hover:text-teal">{{ file.name }}</a>
						<div class="flex shrink-0 items-center gap-16">
							<span>{{ size(file.size) }}</span>
							<button type="button" title="Löschen" class="size-18 hover:text-teal" @click="remove(file)"><IconTrash /></button>
						</div>
					</div>
				</article>
				<p v-if="!page.files.length" class="mt-16 sm:mt-32">Es sind keine Dokumente vorhanden.</p>

				<!-- The expert portal's upload box; here a drop uploads at once, as there is no form around it. -->
				<div class="mt-24 sm:mt-48">
					<DropBox :accept="page.uploads.accept" :restrictions="page.uploads.restrictions" :class="{ 'pointer-events-none opacity-50': uploading }" @files="upload" />
					<p v-if="uploading" class="pt-8">Wird hochgeladen …</p>
				</div>
			</Collapsible>
		</div>

		<Lightbox v-if="reading" :title="reading.subject" @close="reading = null">
			<p class="mb-12 text-lg">{{ shortDate(reading.created_at) }}, {{ reading.author }}<template v-if="reading.recipients !== null">, an {{ reading.recipients }} Teilnehmer</template></p>
			<!-- Through the site's allowlist on the server ([[RichText]]). -->
			<div class="text-lg [&_a]:underline [&_li]:ml-20 [&_li]:list-disc [&_p+p]:mt-12" v-html="reading.body" />
			<ul v-if="reading.attachments.length" class="mt-24 border-t-2 border-gray-600 pt-8 text-lg">
				<li v-for="file in reading.attachments" :key="file.uuid"><a :href="file.url" target="_blank" class="underline hover:text-teal">{{ file.name }}</a></li>
			</ul>
		</Lightbox>

		<Lightbox v-if="adding" title="Teilnehmer hinzufügen" @close="closeAdding">
			<SearchField v-model="search" />
			<p v-if="search && !searching && !found.length" class="mt-16 text-lg">Keine Studenten gefunden.</p>
			<ul v-if="found.length" class="mt-16 text-lg">
				<li v-for="student in found" :key="student.uuid" class="flex items-center justify-between gap-16 border-b border-gray-400 py-8">
					<span class="min-w-0">{{ student.name }}<template v-if="student.city">, {{ student.city }}</template><br /><span class="text-md text-gray-600">{{ student.email }}</span></span>
					<em v-if="booked.has(student.uuid)" class="shrink-0 italic">bereits gebucht</em>
					<Button v-else class="shrink-0" :disabled="booking === student.uuid" @click="add(student)">Hinzufügen</Button>
				</li>
			</ul>
		</Lightbox>
	</section>
</template>
