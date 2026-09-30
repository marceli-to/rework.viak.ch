<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { closeEvent, deleteEvent, fetchEvent, fetchEventPage, saveEvent, setEventState } from '@/api/events';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { courseNumber } from '@/support/format';
import ActionBox from '@/components/form/ActionBox.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import NoResults from '@/components/ui/NoResults.vue';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Veranstaltung hinzufügen* / *bearbeiten* ([[07-dashboard]], step 6): the fields
 * are [[EventSchema]]. A new one is created under the course in the path.
 *
 * **Legacy's state boxes** (its `event/Form.vue`), between *Speichern* and the
 * delete box: green *Veranstaltung bestätigen*, orange *Veranstaltung
 * absagen*, each behind a confirm, turning into *bestätigt am …* /
 * *abgesagt am …* once done. Each mails the participants and experts
 * ([[SendConfirmationMails]], [[SendEventCancelMails]]). Once the date has
 * run, green *Veranstaltung abschliessen*, which **asks who attended first**
 * (Marcel, 2026-09-29): a lightbox, *Teilnehmer «Kurs»*, lists the seats, none ticked
 * and at least one wanted, and
 * *Abschliessen und Bestätigungen senden* records the ticks and closes in one
 * request ([[EventPageController::close]]). The ticked seats get the
 * participation confirmation ([[SendClosingMails]]); the date's page then
 * shows each seat's attendance as a badge.
 */
const route = useRoute();
const save = (uuid, form) => saveEvent(uuid, form, route.params.course);

const day = (iso) => (iso ? iso.split('-').reverse().join('.') : '');
const bookings = (count) => (count === 1 ? 'eine Buchung' : `${count} Buchungen`);

const busy = ref(false);

/** Confirm or cancel, after asking, then show what the server says now. */
async function act(meta, patchMeta, state, question) {
	if (!(await confirm(question, `${courseNumber(meta.course.number)} ${meta.course.title}`))) return;

	busy.value = true;
	try {
		await setEventState(meta.uuid, state);
		const now = await fetchEvent(meta.uuid);
		patchMeta({ state: now.state, confirmed_at: now.confirmed_at, cancelled_at: now.cancelled_at, closed_at: now.closed_at });
		toast({ confirmed: 'Veranstaltung bestätigt', cancelled: 'Veranstaltung abgesagt', closed: 'Veranstaltung abgeschlossen' }[state]);
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = false;
	}
}

// Veranstaltung abschliessen
const closing = ref(null);

async function startClosing(meta, patchMeta) {
	busy.value = true;
	try {
		const { participants } = await fetchEventPage(meta.uuid);
		closing.value = { meta, patchMeta, participants, attended: [], error: null };
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = false;
	}
}

async function close() {
	const { meta, patchMeta, attended, participants } = closing.value;

	// Said here before the server says it ([[EventPageController::close]]).
	if (participants.length && !attended.length) {
		closing.value.error = 'Bitte mindestens einen Teilnehmer auswählen.';
		return;
	}

	busy.value = true;
	try {
		await closeEvent(meta.uuid, attended);
		const now = await fetchEvent(meta.uuid);
		patchMeta({ state: now.state, closed_at: now.closed_at });
		closing.value = null;
		toast('Veranstaltung abgeschlossen');
	} catch (problem) {
		if (problem.errors?.attended) closing.value.error = problem.errors.attended[0];
		else toast(problem.message, 'error');
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<ResourceForm
		schema="event"
		:load="fetchEvent"
		:save="save"
		:remove="deleteEvent"
		:list="{ name: 'courses' }"
		:edit="(uuid) => ({ name: 'event.edit', params: { uuid } })"
		noun="Veranstaltung"
		:titles="{ create: 'Veranstaltung hinzufügen', edit: (meta) => `Veranstaltung für\n${meta.course.title}` }"
		:deletion="{
			title: 'Veranstaltung löschen',
			text: 'Mit dieser Aktion wird die Veranstaltung gelöscht.',
			question: (form, meta) => `${courseNumber(meta.course.number)} ${meta.course.title}, ${day(form.dates[0]?.date)}`,
		}"
		:blocked="(meta) => (meta.bookings ? `Diese Veranstaltung kann nicht gelöscht werden, da ${bookings(meta.bookings)} vorhanden sind.` : null)"
		:locked="(meta) => meta.is_past"
		:stay="false"
	>
		<template #actions="{ meta, patchMeta }">
			<ActionBox v-if="meta.state === 'cancelled'" tone="warning">
				<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung abgesagt</h2>
				<p>Diese Veranstaltung wurde am {{ meta.cancelled_at }} abgesagt.</p>
			</ActionBox>

			<!-- Legacy's closing box, once the date has run. Its text said the
			     experts are told as well; legacy's handler never mailed them,
			     so it says what happens. -->
			<ActionBox v-else-if="meta.is_past" tone="success">
				<template v-if="meta.state === 'closed'">
					<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung abgeschlossen</h2>
					<p>Diese Veranstaltung wurde am {{ meta.closed_at }} abgeschlossen.</p>
				</template>
				<template v-else>
					<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung abschliessen</h2>
					<p class="mb-12 lg:mb-16">Mit dieser Aktion wird die Veranstaltung abgeschlossen. Zuerst wird gefragt, wer teilgenommen hat: diese Teilnehmer erhalten per E-Mail eine Teilnahmebestätigung.</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="success" class="w-full" :disabled="busy" @click="startClosing(meta, patchMeta)">Abschliessen</Button>
					</div>
				</template>
			</ActionBox>

			<template v-else-if="!meta.is_past">
				<ActionBox tone="success">
					<template v-if="meta.state === 'confirmed'">
						<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung bestätigt</h2>
						<p>Diese Veranstaltung wurde am {{ meta.confirmed_at }} bestätigt.</p>
					</template>
					<template v-else>
						<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung bestätigen</h2>
						<p class="mb-12 lg:mb-16">Mit dieser Aktion wird die Durchführung der Veranstaltung bestätigt. Die Teilnehmer und Experten werden per E-Mail informiert.</p>
						<div class="mt-12 sm:mt-24">
							<Button variant="success" class="w-full" :disabled="busy" @click="act(meta, patchMeta, 'confirmed', 'Bitte «Veranstaltung bestätigen» bestätigen!')">Bestätigen</Button>
						</div>
					</template>
				</ActionBox>

				<ActionBox tone="warning">
					<h2 class="mb-8 font-bold sm:mb-16">Veranstaltung absagen</h2>
					<p class="mb-12 lg:mb-16">Mit dieser Aktion wird die Veranstaltung abgesagt. Für den Kurs angemeldete Studenten werden per Mail informiert.</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="warning" class="w-full" :disabled="busy" @click="act(meta, patchMeta, 'cancelled', 'Bitte «Veranstaltung absagen» bestätigen!')">Absagen</Button>
					</div>
				</ActionBox>
			</template>
		</template>
	</ResourceForm>

	<!-- Who attended, asked at the moment it matters: nobody ticked, at least one wanted. -->
	<!-- The course in the title; the text at the checkboxes' own size; each row
	     clickable across its width, the label stretched over it. -->
	<Lightbox v-if="closing" :title="`Teilnehmer «${closing.meta.course.title}»`" @close="closing = null">
		<p class="text-md sm:text-lg lg:text-xl">Angekreuzte Teilnehmer erhalten eine Teilnahmebestätigung per E-Mail. Danach lässt sich die Teilnahme nicht mehr ändern.</p>

		<ul v-if="closing.participants.length" class="mt-24">
			<li v-for="participant in closing.participants" :key="participant.uuid" class="border-t border-black hover:text-teal [&_input]:mt-11 sm:[&_input]:mt-11 lg:[&_input]:mt-13 [&_label]:flex-1 [&_label]:py-8">
				<Checkbox v-model="closing.attended" :value="participant.uuid">
					{{ participant.name }}<template v-if="participant.city">, {{ participant.city }}</template>
				</Checkbox>
			</li>
		</ul>
		<NoResults v-else>Diese Veranstaltung hat keine Teilnehmer.</NoResults>
		<p v-if="closing.error && !closing.attended.length" class="mt-16 text-md text-danger lg:text-lg">{{ closing.error }}</p>

		<div class="mt-32 flex flex-col items-center [&>*]:w-full [&>*]:max-w-400 [&>*+*]:mt-12">
			<Button :disabled="busy" @click="close">{{ closing.participants.length ? 'Abschliessen und Bestätigungen senden' : 'Abschliessen' }}</Button>
			<Button variant="gray-outline" @click="closing = null">Abbrechen</Button>
		</div>
	</Lightbox>
</template>
