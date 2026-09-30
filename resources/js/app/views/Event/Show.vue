<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { bookStudent, confirmAttendance, downloadParticipants, fetchEventPage, removeFile } from '@/api/events';
import { fetchStudents } from '@/api/students';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { returnTo } from '@/router';
import { courseNumber, shortDate } from '@/support/format';
import ArticleText from '@/components/layout/ArticleText.vue';
import AttendanceBadge from '@/components/course/AttendanceBadge.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EventRow from '@/components/course/EventRow.vue';
import FileRow from '@/components/list/FileRow.vue';
import IconDownload from '@/components/icons/Download.vue';
import IconPlus from '@/components/icons/Plus.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Loading from '@/components/ui/Loading.vue';
import MessageRow from '@/components/list/MessageRow.vue';
import SearchField from '@/components/list/SearchField.vue';
import NoResults from '@/components/ui/NoResults.vue';

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
 *   On a closed date, a seat left unticked has *Bestätigen* under its badge
 *   (Marcel, 2026-09-30): attended after all, and its confirmation sent.
 * - *Nachrichten*, legacy's messages module ([[MessageRow]]): a row per note
 *   that opens it in legacy's box, and the plus to *Nachricht erstellen*. Only with participants,
 *   as legacy.
 * - *Kurs-Dokumente*: legacy's rows ([[FileRow]], the portal's
 *   `row/file`), *Download* over *Löschen*, and the plus to *Dokumente
 *   hochladen*, a screen of its own as legacy has it.
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

// A seat missed at closing ([[EventPageController::confirm]]).
const confirming = ref(null);

async function confirmSeat(participant) {
	if (!(await confirm('Teilnahme bestätigen?', `${participant.name} wird als teilgenommen erfasst und erhält die Teilnahmebestätigung per E-Mail.`))) return;

	confirming.value = participant.uuid;
	try {
		await confirmAttendance(page.value.event.uuid, participant.uuid);
		participant.participated = true;
		toast(`Die Teilnahmebestätigung an ${participant.name} wird gesendet.`);
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		confirming.value = null;
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

// Kurs-Dokumente
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

</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<section v-else>
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal">{{ courseNumber(page.course.number) }} {{ page.course.title }}</h1>
				<BackLink :to="returnTo({ name: 'courses' })" />
			</template>
		</ArticleText>

		<!-- The aside has no column beside it here, so the lists keep clear of *Zurück*. -->
		<div class="mt-32 sm:mt-48">
			<Collapsible expanded :count="page.participants.length">
				<template #title>Informationen</template>
				<EventRow :event="page.event" :details="false" />
			</Collapsible>

			<Collapsible expanded>
				<template #title>Teilnehmer</template>

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
						<div class="col-span-6 flex flex-col items-end gap-8 sm:col-span-2">
							<!-- Recorded when the date was closed ([[EventPageController::close]]). -->
							<AttendanceBadge :participated="participant.participated" :closed="closed" />
							<Button v-if="closed && !participant.participated" variant="success" :disabled="confirming === participant.uuid" @click="confirmSeat(participant)">Bestätigen</Button>
						</div>
					</article>
				</template>
				<NoResults v-else>Es sind keine Anmeldungen für diesen Kurs vorhanden.</NoResults>

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

			<Collapsible v-if="page.participants.length" :count="page.messages.length">
				<template #title>Nachrichten</template>

				<!-- The portals' message row and box ([[MessageRow]], `row/message.blade.php`). -->
				<MessageRow v-for="message in page.messages" :key="message.uuid" :message="message" />
				<NoResults v-if="!page.messages.length">Es sind noch keine Nachrichten vorhanden.</NoResults>

				<div class="mt-24 flex">
					<RouterLink :to="{ name: 'event.message', params: { uuid: page.event.uuid } }" title="Nachricht erstellen" class="block hover:text-teal">
						<IconPlus size="lg" class="block" />
					</RouterLink>
				</div>
			</Collapsible>

			<Collapsible :count="page.files.length">
				<template #title>Kurs-Dokumente</template>

				<!-- The portal's file row ([[FileRow]]): name, uploaded, size, *Download* over *Löschen*. -->
				<FileRow v-for="file in page.files" :key="file.uuid" :file="file">
					<template #action>
						<Button variant="secondary" class="w-full" @click="remove(file)">Löschen</Button>
					</template>
				</FileRow>
				<NoResults v-if="!page.files.length">Es sind keine Dokumente vorhanden.</NoResults>

				<!-- Legacy's plus to *Dokumente hochladen*, a screen of its own. -->
				<div class="mt-24 flex">
					<RouterLink :to="{ name: 'event.upload', params: { uuid: page.event.uuid } }" title="Dokumente hochladen" class="block hover:text-teal">
						<IconPlus size="lg" class="block" />
					</RouterLink>
				</div>
			</Collapsible>
		</div>

		

		<Lightbox v-if="adding" title="Teilnehmer hinzufügen" @close="closeAdding">
			<SearchField v-model="search" />
			<NoResults v-if="search && !searching && !found.length">Keine Studenten gefunden.</NoResults>
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
