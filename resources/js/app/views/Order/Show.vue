<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { cancelOrder, fetchOrder, setDispatched } from '@/api/licenceOrders';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { returnTo } from '@/router';
import { shortDate } from '@/support/format';
import BackLink from '@/components/ui/BackLink.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Loading from '@/components/ui/Loading.vue';
import PaymentBadge from '@/components/order/PaymentBadge.vue';

/**
 * A licence order's own page ([[05-licences]], [[LicenceOrderResource]]): who
 * ordered, where the licences go, the invoice, and **each line with
 * *Versendet***. A line is marked once VIAK has forwarded the licence; the
 * badge then says who and when, which is the answer when one never arrived.
 * *Versand offen* takes a mistaken tick off again (Marcel, 2026-10-08).
 *
 * The head is not the forms' aside and column: the order is a record, so it
 * reads as *Rechnungen*' row, labelled columns over a rule (Marcel picked it
 * from four on 2026-10-08, over a fact list and the homepage's grey panel).
 *
 * *Stornieren* under the lines, **while nothing has been sent** (Marcel,
 * 2026-10-20), what it does said in its dialog: the invoice is cancelled and
 * reversed in the books; a paid one is refunded by hand.
 */
const route = useRoute();
const order = ref(null);
const error = ref(null);
const busy = ref(null);

onMounted(async () => {
	try {
		order.value = await fetchOrder(route.params.uuid);
	} catch (problem) {
		error.value = problem.message;
	}
});

const chf = (value) => `CHF ${Number(value).toFixed(2)}`;
const open = computed(() => order.value?.items.filter((item) => !item.dispatched_at).length ?? 0);
const sent = computed(() => order.value?.items.some((item) => item.dispatched_at) ?? false);
const cancelling = ref(false);

async function cancel() {
	const paid = order.value.payment === 'paid';
	const what = order.value.invoice ? `Die Bestellung und ihre Rechnung ${order.value.invoice.number} werden` : 'Die Bestellung wird';
	const text = `${what} storniert, die Bestellung steht danach unter «Stornierte Bestellungen».`
		+ (paid ? ' Sie ist bezahlt: den Betrag von Hand zurückzahlen.' : '');
	if (!(await confirm('Bestellung stornieren?', text))) return;

	cancelling.value = true;
	try {
		order.value = await cancelOrder(order.value.uuid);
		toast('Die Bestellung ist storniert.');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		cancelling.value = false;
	}
}

async function dispatch(item, dispatched) {
	if (!dispatched && !(await confirm('Versand wieder offen?', `${item.title} wird wieder als offen geführt.`))) return;

	busy.value = item.uuid;
	try {
		order.value = await setDispatched(item.uuid, dispatched);
		if (dispatched) toast(open.value ? `Versendet. Noch ${open.value} offen.` : 'Versendet. Die Bestellung ist erledigt.');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = null;
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!order" />

	<section v-else>
		<!-- *Rechnungen*' row: the order as labelled columns over a rule (Marcel, 2026-10-08). -->
		<!-- *Zurück* under the title on the left, as every other screen's aside has it. -->
		<h1 class="font-bold text-teal">Bestellung {{ order.number }}</h1>
		<BackLink :to="returnTo({ name: 'backoffice.orders' })" />
		<p v-if="order.cancelled_at" class="mt-16 flex flex-wrap gap-8">
			<Badge>Storniert am {{ shortDate(order.cancelled_at.slice(0, 10)) }}<template v-if="order.cancelled_by"> von {{ order.cancelled_by }}</template></Badge>
			<Badge v-if="order.refund" variant="warning">Bezahlt: von Hand zurückzahlen</Badge>
		</p>
		<div class="mt-24 grid grid-cols-12 gap-x-16 gap-y-16 border-t border-black pt-16 text-lg leading-[1.4] lg:mt-32 lg:gap-x-40 lg:text-xl">
			<div class="col-span-6 sm:col-span-2"><div class="text-md text-gray-600 lg:text-lg">Datum</div>{{ shortDate(order.date) }}</div>
			<div class="col-span-6 sm:col-span-3">
				<div class="text-md text-gray-600 lg:text-lg">Kunde</div>
				<RouterLink :to="{ name: 'customer.show', params: { uuid: order.customer.uuid } }" class="font-bold hover:text-teal">{{ order.customer.name }}</RouterLink><template v-if="order.customer.city">, {{ order.customer.city }}</template>
			</div>
			<div class="col-span-12 min-w-0 sm:col-span-3"><div class="text-md text-gray-600 lg:text-lg">Lizenzen an</div><a :href="`mailto:${order.delivery_email}`" class="break-words hover:text-teal">{{ order.delivery_email }}</a></div>
			<!-- Four columns, so the payment badge sits on the amount's line (Marcel, 2026-10-08). -->
			<div class="col-span-12 sm:col-span-4">
				<div class="text-md text-gray-600 lg:text-lg">Rechnung</div>
				<div class="flex flex-wrap items-center gap-x-8 gap-y-4">
					<span v-if="order.invoice"><a v-if="order.invoice.document" :href="order.invoice.document" class="hover:text-teal">{{ order.invoice.number }}</a><template v-else>{{ order.invoice.number }}</template>, <span class="tabular-nums">{{ chf(order.invoice.grand_total) }}</span></span>
					<PaymentBadge :payment="order.payment" />
				</div>
			</div>
		</div>

		<div class="mt-32 sm:mt-48">
			<Collapsible expanded :count="order.items.length">
				<template #title>Positionen</template>

				<article v-for="(item, index) in order.items" :key="item.uuid" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
					<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
						<div class="sm:col-span-7">
							<strong class="font-bold"><template v-if="item.quantity > 1">{{ item.quantity }} × </template>{{ item.title }}</strong>
							<template v-if="item.host"><br />für {{ item.host }}</template>
							<div class="mt-8 flex flex-wrap gap-8">
								<Badge v-if="item.sku">{{ item.sku }}</Badge>
								<Badge v-else-if="item.free_line">Freie Position</Badge>
								<Badge>{{ chf(item.net) }}</Badge>
								<Badge v-if="item.dispatched_at" variant="success">Versendet am {{ shortDate(item.dispatched_at.slice(0, 10)) }}<template v-if="item.dispatched_by"> von {{ item.dispatched_by }}</template></Badge>
							</div>
						</div>
						<div v-if="!order.cancelled_at" class="mt-16 flex flex-col items-stretch gap-8 sm:col-span-5 sm:mt-0 sm:items-end">
							<Button v-if="!item.dispatched_at" variant="success" :disabled="busy === item.uuid" @click="dispatch(item, true)">Versendet</Button>
							<Button v-else variant="secondary" :disabled="busy === item.uuid" @click="dispatch(item, false)">Versand offen</Button>
							<!-- *Stornieren* right under the last line's *Versendet*, the
							     explanation in its dialog (Marcel, 2026-10-20). Gone once
							     anything has been sent. -->
							<Button v-if="index === order.items.length - 1 && !sent" variant="danger" :disabled="cancelling" @click="cancel">{{ cancelling ? 'Wird storniert …' : 'Stornieren' }}</Button>
						</div>
					</div>
				</article>

			</Collapsible>
		</div>
	</section>
</template>
