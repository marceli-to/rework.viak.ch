<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { cancelBooking, fetchCustomerPage } from '@/api/customers';
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
import EditableListItem from '@/components/list/EditableListItem.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';
import PaymentBadge from '@/components/order/PaymentBadge.vue';

/**
 * A student's own page — legacy's `student/Show.vue` ([[07-dashboard]], step 7):
 * the address, *Gebuchte Kurse* with *Annullieren*, *Absolvierte Kurse*,
 * *Dokumente*. What changed ([[CustomerPageController]]):
 *
 * - **The lists split on the course's date**, not legacy's flags, so a course
 *   that has run never keeps a live *Annullieren*.
 * - ***Annullierte Kurse*** is new, with who cancelled.
 * - **The admin decides whether a late cancellation costs** (#14): the dialog
 *   names the amount and offers both. Outside the window it only asks.
 * - Every document on the page, where legacy showed five and linked the rest.
 * - ***Bestellungen***, the licence orders ([[05-licences]]).
 */
const route = useRoute();
const page = ref(null);
const error = ref(null);
const busy = ref(null);

async function load() {
	try {
		page.value = await fetchCustomerPage(route.params.uuid);
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
	const who = `${page.value.customer.name} wird von ${booking.course.title} (${shortDate(booking.event.date)}) abgemeldet.`;

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
				<h1 class="font-bold text-teal">Profil Kunde</h1>
				<BackLink :to="returnTo({ name: 'customers' })" />
			</template>

			<p>
				<template v-if="page.customer.company">{{ page.customer.company }}<br /></template>
				{{ page.customer.name }}<br />
				<template v-if="page.customer.street">{{ page.customer.street }}<br /></template>
				{{ page.customer.city }}<template v-if="page.customer.country"><br />{{ page.customer.country }}</template>
			</p>
			<p class="mt-16">
				<a :href="`mailto:${page.customer.email}`" class="hover:text-teal">{{ page.customer.email }}</a><br />
				<a v-if="page.customer.phone" :href="`tel:${page.customer.phone}`" class="hover:text-teal">{{ page.customer.phone }}</a>
			</p>
			<p v-if="page.customer.deactivated_at" class="mt-16"><Badge variant="danger">Konto deaktiviert seit {{ shortDate(page.customer.deactivated_at.slice(0, 10)) }}</Badge></p>
			<div class="mt-24 sm:flex"><Button :to="{ name: 'customer.edit', params: { uuid: page.customer.uuid } }">Bearbeiten</Button></div>
		</ArticleText>

		<div class="mt-32 sm:mt-48">
			<Collapsible expanded :count="page.booked.length">
				<template #title>Gebuchte Kurse</template>
				<BookingRow v-for="booking in page.booked" :key="booking.uuid" :booking="booking">
					<template #badges>
						<Badge v-if="booking.has_rental">Mietcomputer</Badge>
					</template>
					<template #actions>
						<Button v-if="!booking.deleted" variant="danger" :disabled="busy === booking.uuid" @click="cancel(booking)">Annullieren</Button>
					</template>
				</BookingRow>
				<NoResults v-if="!page.booked.length">Kunde hat noch keine gebuchten Kurse.</NoResults>
			</Collapsible>

			<Collapsible :count="page.past.length">
				<template #title>Absolvierte Kurse</template>
				<BookingRow v-for="booking in page.past" :key="booking.uuid" :booking="booking">
					<template #badges>
						<AttendanceBadge :participated="booking.participated" :closed="booking.event.state === 'closed'" />
					</template>
				</BookingRow>
				<NoResults v-if="!page.past.length">Kunde hat noch keine absolvierten Kurse.</NoResults>
			</Collapsible>

			<Collapsible v-if="page.cancelled.length" :count="page.cancelled.length">
				<template #title>Annullierte Kurse</template>
				<BookingRow v-for="booking in page.cancelled" :key="booking.uuid" :booking="booking">
					<template #badges>
						<Badge variant="danger">Annulliert am {{ shortDate(booking.cancelled_at.slice(0, 10)) }}</Badge>
						<Badge v-if="booking.reason">{{ booking.reason }}</Badge>
					</template>
				</BookingRow>
			</Collapsible>

			<!-- Licence orders ([[05-licences]]): *Bestellungen*' row. Entered from *Bestellungen*, not here (Marcel, 2026-10-08). -->
			<Collapsible :count="page.orders.length">
				<template #title>Bestellungen</template>
				<EditableListItem v-for="order in page.orders" :key="order.uuid" :show="{ name: 'backoffice.order.show', params: { uuid: order.uuid } }" wide>
					<div class="col-span-12 sm:col-span-4">
						<RouterLink :to="{ name: 'backoffice.order.show', params: { uuid: order.uuid } }" class="hover:text-teal">{{ order.number }}</RouterLink><br />
						{{ shortDate(order.date) }}
					</div>
					<div class="col-span-12 pr-40 sm:col-span-8">
						<div v-for="(line, index) in order.lines" :key="index">{{ line }}</div>
						<div class="mt-8 flex flex-wrap gap-8">
							<PaymentBadge :payment="order.payment" />
							<Badge v-if="order.open" variant="warning">{{ order.open === order.lines.length ? 'Nicht versendet' : `${order.open} nicht versendet` }}</Badge>
							<Badge v-else variant="success">Versendet</Badge>
						</div>
					</div>
				</EditableListItem>
				<NoResults v-if="!page.orders.length">Kunde hat noch keine Software bestellt.</NoResults>

			</Collapsible>

			<Collapsible :count="page.documents.length">
				<template #title>Dokumente</template>
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
				<NoResults v-if="!page.documents.length">Es sind noch keine Dokumente vorhanden.</NoResults>
			</Collapsible>
		</div>
	</section>
</template>
