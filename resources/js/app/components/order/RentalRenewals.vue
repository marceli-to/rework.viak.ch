<script setup>
import { onMounted, ref } from 'vue';
import { fetchRenewals, sendRenewalReminder } from '@/api/licenceOrders';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { shortDate } from '@/support/format';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Mietlizenzen, die bald auslaufen* (#83, [[RentalRenewals]]): the rentals
 * whose year (order date plus one year) ends within 60 days or ended in the
 * last 30, soonest first, until the customer orders the same licence again.
 * A line as on the order's page, *Erinnerung senden* on the right (Marcel,
 * 2026-11-25: a button, no automatic mail). Shut, with its count, as the
 * other lists under *Bestellungen*.
 */
const rows = ref([]);
const loaded = ref(false);
const busy = ref(null);
const today = new Date().toISOString().slice(0, 10);

onMounted(async () => {
	try {
		rows.value = await fetchRenewals();
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		loaded.value = true;
	}
});

async function remind(row) {
	const again = row.reminded_at ? ` Die letzte ging am ${shortDate(row.reminded_at)} raus.` : '';
	if (!(await confirm('Erinnerung senden?', `${row.customer.name} (${row.customer.email}) erhält eine E-Mail, dass «${row.line}» am ${shortDate(row.ends)} ausläuft, mit einem Link zum Verlängern.${again}`))) return;

	busy.value = row.uuid;
	try {
		const fresh = await sendRenewalReminder(row.uuid);
		rows.value = rows.value.map((item) => (item.uuid === fresh.uuid ? fresh : item));
		toast('Die Erinnerung ist gesendet.');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = null;
	}
}
</script>

<template>
	<Collapsible v-if="loaded" :count="rows.length">
		<template #title>Mietlizenzen, die bald auslaufen</template>

		<article v-for="row in rows" :key="row.uuid" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
			<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
				<div class="sm:col-span-7">
					<strong class="font-bold">{{ row.line }}</strong>
					<br />{{ row.customer.name }}, Bestellung
					<RouterLink :to="{ name: 'backoffice.order.show', params: { uuid: row.order.uuid } }" class="underline decoration-1 underline-offset-[3px] hover:text-teal">{{ row.order.number }}</RouterLink>
					<div class="mt-8 flex flex-wrap gap-8">
						<Badge :variant="row.ends < today ? 'danger' : 'warning'">{{ row.ends < today ? 'Abgelaufen am' : 'Läuft ab am' }} {{ shortDate(row.ends) }}</Badge>
						<Badge v-if="row.reminded_at" variant="success">Erinnert am {{ shortDate(row.reminded_at) }}<template v-if="row.reminded_by"> von {{ row.reminded_by }}</template></Badge>
					</div>
				</div>
				<div class="mt-16 flex flex-col items-stretch gap-8 sm:col-span-5 sm:mt-0 sm:items-end">
					<Button :variant="row.reminded_at ? 'secondary' : 'primary'" :disabled="busy === row.uuid" @click="remind(row)">
						{{ busy === row.uuid ? 'Wird gesendet …' : row.reminded_at ? 'Nochmals erinnern' : 'Erinnerung senden' }}
					</Button>
				</div>
			</div>
		</article>
		<NoResults v-if="!rows.length">Es laufen keine Mietlizenzen bald aus.</NoResults>
	</Collapsible>
</template>
