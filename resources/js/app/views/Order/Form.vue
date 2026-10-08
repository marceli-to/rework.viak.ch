<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrderForm, placeOrder } from '@/api/licenceOrders';
import { toast } from '@/composables/useToast';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Field from '@/components/form/Field.vue';
import Loading from '@/components/ui/Loading.vue';
import Select from '@/components/form/Select.vue';

/**
 * *Bestellung erfassen* ([[05-licences]], #36): an order VIAK took by mail or
 * phone, for one customer, reached from *Bestellungen* or the customer's page.
 *
 * Each position is a variant from the whole catalogue, **the ones not in the
 * shop included** (EDU, labs), or ***Freie Position***, a title and a net
 * price typed in: the one-off licence for a few months that VIAK prices by
 * hand. A plugin asks for its host software. A variant with a minimum starts
 * at it; it is not enforced, as an order by phone is entered as it was sold.
 *
 * Each position is a collapsible, as the course form's sections are, added
 * with the dashed button its videos have (Marcel, 2026-10-08).
 *
 * Saving invoices a priced order at once ([[PlaceLicenceOrder]]); a free one
 * (demos) has no invoice. Either lands on the worklist.
 */
const route = useRoute();
const router = useRouter();

const FREE = 'frei';

const page = ref(null);
const error = ref(null);
const errors = ref({});
const saving = ref(false);

const invoiceAddress = ref('');
const deliveryEmail = ref('');
const lines = reactive([]);

// `key`: a removed position must not hand its collapsible's state to the next.
let made = 0;
const blank = () => ({ key: ++made, choice: '', title: '', price: '', quantity: 1, host: '' });

onMounted(async () => {
	try {
		page.value = await fetchOrderForm(route.params.customer);
		lines.push(blank());
	} catch (problem) {
		error.value = problem.message;
	}
});

const chf = (value) => `CHF ${Number(value).toFixed(2)}`;

// Each variant once, with its product: the label, price, minimum and hosts.
const variants = computed(() => new Map((page.value?.products ?? []).flatMap((product) => product.variants.map((variant) => [variant.uuid, { ...variant, product }]))));

const options = computed(() => [
	{ value: FREE, label: 'Freie Position' },
	...(page.value?.products ?? []).map((product) => ({
		label: product.title,
		options: product.variants.map((variant) => ({
			value: variant.uuid,
			// The product in front: a chosen option shows without its group's heading.
			label: `${product.title}, ${variant.label}, ${chf(variant.price)}${variant.listed ? '' : ' (nicht im Shop)'}`,
		})),
	})),
]);

const addressOptions = computed(() => (page.value?.addresses ?? []).map((address) => ({ value: address.uuid, label: address.label })));

function choose(line, choice) {
	line.choice = choice;
	line.host = '';
	const variant = variants.value.get(choice);
	line.quantity = Math.max(variant?.min_quantity ?? 1, 1);
}

// The collapsible's title: the product, or the free line's typed title.
const heading = (line) => (line.choice === FREE ? line.title || 'Freie Position' : variants.value.get(line.choice)?.product.title ?? '');

const unit = (line) => (line.choice === FREE ? Number(line.price) || 0 : Number(variants.value.get(line.choice)?.price ?? 0));
const cents = (value) => Math.round(value * 100);

// As the invoice adds up: VAT per line, to the centime ([[Vat]]).
const totals = computed(() => {
	const rate = Number(page.value?.vat_rate ?? 0);
	const net = lines.map((line) => cents(unit(line) * (Number(line.quantity) || 0)));
	const vat = net.map((amount) => Math.round((amount * rate) / 100));
	const sum = (list) => list.reduce((a, b) => a + b, 0) / 100;

	return { net: sum(net), vat: sum(vat), total: sum(net) + sum(vat), rate };
});

const lineError = (index, key) => errors.value[`lines.${index}.${key}`]?.[0];

async function save() {
	saving.value = true;
	errors.value = {};

	try {
		const order = await placeOrder(route.params.customer, {
			invoice_address: invoiceAddress.value || null,
			delivery_email: deliveryEmail.value || null,
			lines: lines.map((line) =>
				line.choice === FREE
					? { free: true, title: line.title, price: line.price, quantity: line.quantity }
					: { variant: line.choice || null, quantity: line.quantity, host: line.host || null },
			),
		});
		toast('Die Bestellung ist erfasst.');
		router.replace({ name: 'backoffice.order.show', params: { uuid: order.uuid } });
	} catch (problem) {
		errors.value = problem.errors ?? {};
		if (!Object.keys(errors.value).length) toast(problem.message, 'error');
	} finally {
		saving.value = false;
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<ArticleText v-else>
		<template #aside>
			<h1 class="font-bold text-teal">Bestellung erfassen</h1>
			<p class="text-md sm:mt-12 sm:text-lg lg:text-xl">Für {{ page.customer.address }}.</p>
			<BackLink :to="{ name: 'backoffice.orders' }" />
		</template>

		<form @submit.prevent="save">
			<Select v-if="addressOptions.length" v-model="invoiceAddress" label="Rechnungsadresse" :options="addressOptions" placeholder="Adresse aus dem Profil" :error="errors.invoice_address?.[0]" />
			<Field v-model="deliveryEmail" type="email" label="Lizenzen an" :hint="`Leer lassen für ${page.customer.email}.`" :error="errors.delivery_email?.[0]" />

			<!-- A collapsible per position, titled with what it is once chosen, *Entfernen* at its foot,
			     then the course form's dashed *… hinzufügen* across the column ([[FormNode]]). -->
			<div class="mt-48">
				<Collapsible v-for="(line, index) in lines" :key="line.key" expanded :invalid="Object.keys(errors).some((key) => key.startsWith(`lines.${index}.`))">
					<template #title>Position {{ index + 1 }}<span v-if="heading(line)" class="ml-12 font-normal">{{ heading(line) }}</span></template>

					<div class="mt-24">
						<Select :model-value="line.choice" label="Software" :options="options" placeholder="Bitte wählen" required :error="lineError(index, 'variant')" @update:model-value="(value) => choose(line, value)" />

						<template v-if="line.choice === FREE">
							<Field v-model="line.title" label="Bezeichnung" required :error="lineError(index, 'title')" />
							<Field v-model="line.price" label="Preis (CHF, netto)" required :error="lineError(index, 'price')" />
						</template>

						<Select
							v-if="variants.get(line.choice)?.product.hosts.length"
							v-model="line.host"
							label="Host-Software"
							:options="variants.get(line.choice).product.hosts.map((host) => ({ value: host, label: host }))"
							placeholder="Bitte wählen"
							required
							:error="lineError(index, 'host')"
						/>

						<Field
							v-model="line.quantity"
							type="number"
							label="Anzahl"
							required
							:hint="variants.get(line.choice)?.min_quantity ? `Im Shop mindestens ${variants.get(line.choice).min_quantity}.` : null"
							:error="lineError(index, 'quantity')"
						/>

						<div v-if="lines.length > 1" class="flex justify-end">
							<button type="button" class="transition-colors hover:text-teal sm:text-lg lg:text-xl" @click="lines.splice(index, 1)">Entfernen</button>
						</div>
					</div>
				</Collapsible>
			</div>

			<p v-if="errors.lines" class="mb-16 text-md text-danger lg:text-lg">{{ errors.lines[0] }}</p>

			<button type="button" class="block w-full border border-dashed border-black py-24 text-center transition-colors hover:border-teal hover:text-teal sm:text-lg lg:text-xl" @click="lines.push(blank())">
				Position hinzufügen
			</button>

			<!-- What the invoice will say, as rows with grey rules between them, the total over a black one.
			     A free order raises none. -->
			<dl class="mt-48 mb-16 divide-y divide-gray-400 border-y border-black text-lg tabular-nums lg:mb-32 lg:text-xl">
				<div class="flex justify-between gap-16 py-10"><dt>Netto</dt><dd>{{ chf(totals.net) }}</dd></div>
				<div class="flex justify-between gap-16 py-10"><dt>MWST {{ totals.rate }} %</dt><dd>{{ chf(totals.vat) }}</dd></div>
				<div class="flex justify-between gap-16 py-10 font-bold"><dt>Total</dt><dd>{{ chf(totals.total) }}</dd></div>
			</dl>
			<p class="mb-16 text-md text-gray-600 lg:mb-32 lg:text-lg">
				{{ totals.net > 0 ? 'Die Rechnung wird beim Erfassen erstellt.' : 'Eine kostenlose Bestellung hat keine Rechnung.' }}
			</p>

			<div class="mb-16 lg:mb-32">
				<Button type="submit" class="w-full" :class="{ 'pointer-events-none opacity-60': saving }">{{ saving ? 'Wird erfasst …' : 'Bestellung erfassen' }}</Button>
			</div>
		</form>
	</ArticleText>
</template>
