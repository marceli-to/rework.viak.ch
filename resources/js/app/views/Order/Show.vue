<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchOrder, setDispatched } from '@/api/licenceOrders';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { returnTo } from '@/router';
import { shortDate } from '@/support/format';
import ArticleText from '@/components/layout/ArticleText.vue';
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
 * *Zurücksetzen* takes a mistaken tick off again.
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

async function dispatch(item, dispatched) {
	if (!dispatched && !(await confirm('Versand zurücksetzen?', `${item.title} wird wieder als offen geführt.`))) return;

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
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal">Bestellung {{ order.number }}</h1>
				<BackLink :to="returnTo({ name: 'backoffice.orders' })" />
			</template>

			<p>
				{{ shortDate(order.date) }}<template v-if="order.entered_by">, erfasst von {{ order.entered_by }}</template><br />
				<RouterLink :to="{ name: 'customer.show', params: { uuid: order.customer.uuid } }" class="hover:text-teal">{{ order.customer.name }}</RouterLink><template v-if="order.customer.city">, {{ order.customer.city }}</template>
			</p>
			<p class="mt-16">
				Lizenzen an <a :href="`mailto:${order.delivery_email}`" class="hover:text-teal">{{ order.delivery_email }}</a>
			</p>
			<p class="mt-16 flex flex-wrap items-center gap-8">
				<template v-if="order.invoice">
					<a v-if="order.invoice.document" :href="order.invoice.document" class="hover:text-teal">Rechnung {{ order.invoice.number }}</a>
					<span v-else>Rechnung {{ order.invoice.number }}</span>
					<span>{{ chf(order.invoice.grand_total) }}</span>
				</template>
				<PaymentBadge :payment="order.payment" />
			</p>
		</ArticleText>

		<div class="mt-32 sm:mt-48">
			<Collapsible expanded :count="order.items.length">
				<template #title>Positionen</template>

				<article v-for="item in order.items" :key="item.uuid" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
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
						<div class="mt-16 flex items-start sm:col-span-5 sm:mt-0 sm:justify-end">
							<Button v-if="!item.dispatched_at" variant="success" :disabled="busy === item.uuid" @click="dispatch(item, true)">Versendet</Button>
							<Button v-else variant="secondary" :disabled="busy === item.uuid" @click="dispatch(item, false)">Zurücksetzen</Button>
						</div>
					</div>
				</article>
			</Collapsible>
		</div>
	</section>
</template>
