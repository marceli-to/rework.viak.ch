<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { deleteEvent, fetchEvent, saveEvent, setEventState } from '@/api/events';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import ActionBox from '@/components/form/ActionBox.vue';
import Button from '@/components/ui/Button.vue';
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
 * run, green *Veranstaltung abschliessen*: the seats ticked as attended on
 * the date's page get the participation confirmation ([[SendClosingMails]]).
 */
const route = useRoute();
const save = (uuid, form) => saveEvent(uuid, form, route.params.course);

const day = (iso) => (iso ? iso.split('-').reverse().join('.') : '');
const bookings = (count) => (count === 1 ? 'eine Buchung' : `${count} Buchungen`);

const busy = ref(false);

/** Confirm or cancel, after asking, then show what the server says now. */
async function act(meta, patchMeta, state, question) {
	if (!(await confirm(question, `${meta.course.number} ${meta.course.title}`))) return;

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
			question: (form, meta) => `${meta.course.number} ${meta.course.title}, ${day(form.dates[0]?.date)}`,
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
					<p class="mb-12 lg:mb-16">Mit dieser Aktion wird die Veranstaltung abgeschlossen. Wer auf der Seite der Veranstaltung als teilgenommen markiert ist, erhält per E-Mail eine Teilnahmebestätigung.</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="success" class="w-full" :disabled="busy" @click="act(meta, patchMeta, 'closed', 'Bitte «Veranstaltung schliessen» bestätigen!')">Schliessen</Button>
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
</template>
