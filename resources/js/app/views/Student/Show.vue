<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { cancelBooking, fetchStudentPage } from '@/api/students';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { returnTo } from '@/router';
import { shortDate } from '@/support/format';
import ArticleText from '@/components/layout/ArticleText.vue';
import AttendanceBadge from '@/components/course/AttendanceBadge.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Badge from '@/components/ui/Badge.vue';
import BookingRow from '@/components/course/BookingRow.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Loading from '@/components/ui/Loading.vue';

/**
 * A student's own page — legacy's `student/Show.vue` ([[07-dashboard]], step 7):
 * the address, *Gebuchte Kurse* with *Annullieren*, *Absolvierte Kurse*,
 * *Dokumente*. What changed ([[StudentPageController]]):
 *
 * - **The lists split on the course's date**, not legacy's flags, so a course
 *   that has run never keeps a live *Annullieren*.
 * - ***Annullierte Kurse*** is new, with who cancelled.
 * - **The admin decides whether a late cancellation costs** (#14): the dialog
 *   names the amount and offers both. Outside the window it only asks.
 * - Every document on the page, where legacy showed five and linked the rest.
 */
const route = useRoute();
const page = ref(null);
const error = ref(null);
const busy = ref(null);

async function load() {
	try {
		page.value = await fetchStudentPage(route.params.uuid);
	} catch (problem) {
		error.value = problem.message;
	}
}

onMounted(load);

const chf = (value) => `CHF ${Number(value).toFixed(2)}`;

// An invoice's state, as the portal colours it: paid green, overdue red.
const STATUS_TONES = { Bezahlt: 'success', Offen: 'neutral', Fällig: 'danger', Storniert: 'neutral' };

async function cancel(booking) {
	const { penalty } = booking;
	const who = `${page.value.student.name} wird von ${booking.course.title} (${shortDate(booking.event.date)}) abgemeldet.`;

	const answer = penalty.applies
		? await confirm('Bitte Annullation bestätigen!', `${who}\n\nDie kurzfristige Annullation kostet gemäss AGB ${chf(penalty.amount)} (${penalty.rate} % der Kurskosten, abzüglich allfälliger Rabatte). Soll das verrechnet werden?`, {
				choices: [
					{ label: 'Mit Kosten annullieren', value: 'charge' },
					{ label: 'Ohne Kosten annullieren', value: 'waive' },
				],
			})
		: await confirm('Bitte Annullation bestätigen!', who) && 'waive';

	if (!answer) return;

	busy.value = booking.uuid;
	try {
		const done = await cancelBooking(booking.uuid, answer === 'charge');
		toast(done.penalty ? `Die Buchung wurde annulliert. Rechnung ${done.penalty.number} über ${chf(done.penalty.grand_total)} ist erstellt.` : 'Die Buchung wurde annulliert.');
		await load();
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = null;
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<section v-else>
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal">Profil Student</h1>
				<BackLink :to="returnTo({ name: 'students' })" />
			</template>

			<p>
				<template v-if="page.student.company">{{ page.student.company }}<br /></template>
				{{ page.student.name }}<br />
				<template v-if="page.student.street">{{ page.student.street }}<br /></template>
				{{ page.student.city }}<template v-if="page.student.country"><br />{{ page.student.country }}</template>
			</p>
			<p class="mt-16">
				<a :href="`mailto:${page.student.email}`" class="hover:text-teal">{{ page.student.email }}</a><br />
				<a v-if="page.student.phone" :href="`tel:${page.student.phone}`" class="hover:text-teal">{{ page.student.phone }}</a>
			</p>
			<p v-if="page.student.deactivated_at" class="mt-16"><Badge variant="danger">Konto deaktiviert seit {{ shortDate(page.student.deactivated_at.slice(0, 10)) }}</Badge></p>
			<div class="mt-24 sm:flex"><Button :to="{ name: 'student.edit', params: { uuid: page.student.uuid } }">Bearbeiten</Button></div>
		</ArticleText>

		<div class="mt-32 sm:mt-48">
			<Collapsible expanded>
				<template #title>Gebuchte Kurse<Badge v-if="page.booked.length" variant="solid" class="ml-12">{{ page.booked.length }}</Badge></template>
				<BookingRow v-for="booking in page.booked" :key="booking.uuid" :booking="booking">
					<template #badges>
						<AttendanceBadge :participated="booking.participated" :closed="booking.event.state === 'closed'" />
						<Badge v-if="booking.has_rental">Mietcomputer</Badge>
					</template>
					<template #actions>
						<Button v-if="!booking.deleted" variant="outline" :disabled="busy === booking.uuid" @click="cancel(booking)">Annullieren</Button>
					</template>
				</BookingRow>
				<p v-if="!page.booked.length" class="mt-16 sm:mt-32">Student hat noch keine gebuchten Kurse.</p>
			</Collapsible>

			<Collapsible>
				<template #title>Absolvierte Kurse<Badge v-if="page.past.length" variant="solid" class="ml-12">{{ page.past.length }}</Badge></template>
				<BookingRow v-for="booking in page.past" :key="booking.uuid" :booking="booking" :details="false">
					<template #badges>
						<AttendanceBadge :participated="booking.participated" :closed="booking.event.state === 'closed'" />
					</template>
				</BookingRow>
				<p v-if="!page.past.length" class="mt-16 sm:mt-32">Student hat noch keine absolvierten Kurse.</p>
			</Collapsible>

			<Collapsible v-if="page.cancelled.length">
				<template #title>Annullierte Kurse<Badge v-if="page.cancelled.length" variant="solid" class="ml-12">{{ page.cancelled.length }}</Badge></template>
				<BookingRow v-for="booking in page.cancelled" :key="booking.uuid" :booking="booking" :details="false">
					<template #badges>
						<Badge variant="danger">Annulliert am {{ shortDate(booking.cancelled_at.slice(0, 10)) }}</Badge>
					</template>
					<template #facts>
						<div v-if="booking.reason">{{ booking.reason }}</div>
					</template>
				</BookingRow>
			</Collapsible>

			<Collapsible>
				<template #title>Dokumente<Badge v-if="page.documents.length" variant="solid" class="ml-12">{{ page.documents.length }}</Badge></template>
				<!-- The portal's document row (`row/document.blade.php`), 4 / 3 / 5, with legacy's teal *Download* at the end. -->
				<article v-for="document in page.documents" :key="document.uuid" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-4">
							<strong class="font-bold">{{ document.course ?? document.type }}</strong><br />
							{{ shortDate(document.event_date ?? document.date) }}
						</div>
						<div class="sm:col-span-3">{{ document.type }} {{ document.number }}</div>
						<div class="flex items-start justify-between gap-16 sm:col-span-5">
							<div>
								<template v-if="document.grand_total">{{ chf(document.grand_total) }}</template>
								<!-- Beside the amount it qualifies, not on a line of its own (Marcel, 2026-09-29). -->
								<Badge v-if="document.status" :variant="STATUS_TONES[document.status]" class="ml-8 align-middle">{{ document.status }}</Badge>
							</div>
							<Button :href="document.url" target="_blank" class="shrink-0">Download</Button>
						</div>
					</div>
				</article>
				<p v-if="!page.documents.length" class="mt-16 sm:mt-32">Es sind noch keine Dokumente vorhanden.</p>
			</Collapsible>
		</div>
	</section>
</template>
