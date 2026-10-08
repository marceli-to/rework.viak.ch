<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchOrderForm, placeOrder } from '@/api/licenceOrders';
import { toast } from '@/composables/useToast';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import Field from '@/components/form/Field.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';
import PositionDialog from '@/components/order/PositionDialog.vue';
import Select from '@/components/form/Select.vue';

/**
 * *Bestellung erfassen* ([[05-licences]], #36): an order VIAK took by mail or
 * phone, for one customer, reached from *Bestellungen*' `+` only.
 *
 * Each position is a variant from the whole catalogue, **the ones not in the
 * shop included** (EDU, labs), or ***Freie Position***, a title and a net
 * price typed in: the one-off licence for a few months that VIAK prices by
 * hand. A plugin asks for its host software. A variant with a minimum starts
 * at it; it is not enforced, as an order by phone is entered as it was sold.
 *
 * The positions are a list as the product form's *Lizenzen* are, each edited
 * in a lightbox ([[PositionDialog]]), since none has a page before the order
 * is saved (Marcel, 2026-10-08).
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

// `key`: a removed position must not hand its row to the next.
let made = 0;
const blank = () => ({ key: ++made, choice: '', title: '', price: '', quantity: 1, host: '' });

onMounted(async () => {
	try {
		page.value = await fetchOrderForm(route.params.customer);
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

// Which position the lightbox has open: an index, `neu`, or none.
const editing = ref(null);

function keep(line) {
	if (editing.value === 'neu') lines.push(line);
	else lines[editing.value] = line;
	clearErrors(editing.value);
	editing.value = null;
}

function drop() {
	lines.splice(editing.value, 1);
	errors.value = {};
	editing.value = null;
}

// The row's label: the product and the variant's, or the free line's typed title.
function label(line) {
	if (line.choice === FREE) return line.title;
	const variant = variants.value.get(line.choice);
	return variant ? `${variant.product.title}, ${variant.label}` : '';
}

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

// The server's errors for one position, by field, and whether it has any.
const lineErrors = (index) =>
	Object.fromEntries(Object.entries(errors.value).filter(([key]) => key.startsWith(`lines.${index}.`)).map(([key, messages]) => [key.split('.').pop(), messages[0]]));
const failed = (index) => Object.keys(lineErrors(index)).length > 0;

function clearErrors(index) {
	if (index === 'neu') return;
	errors.value = Object.fromEntries(Object.entries(errors.value).filter(([key]) => !key.startsWith(`lines.${index}.`)));
}

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

			<!-- The product form's *Lizenzen* ([[VariantSection]]): a row per position, the label left and
			     its badges right, a pencil each and the `+` under the list, both opening the position
			     in a lightbox (Marcel, 2026-10-08). -->
			<Collapsible class="mt-48" expanded :count="lines.length" :invalid="Boolean(errors.lines) || lines.some((line, index) => failed(index))">
				<template #title>Positionen</template>

				<NoResults v-if="!lines.length">Noch keine Positionen erfasst.</NoResults>
				<EditableListItem v-for="(line, index) in lines" :key="line.key" editable wide @edit="editing = index">
					<div class="col-span-12 sm:col-span-6">
						{{ label(line) }}<template v-if="line.host"><br />für {{ line.host }}</template>
					</div>
					<div class="col-span-12 pr-40 max-sm:mt-8 sm:col-span-6">
						<span class="flex flex-wrap gap-8">
							<Badge v-if="line.choice === FREE">Freie Position</Badge>
							<Badge v-else>{{ variants.get(line.choice)?.sku }}</Badge>
							<Badge v-if="Number(line.quantity) > 1">{{ line.quantity }} Stück</Badge>
							<Badge>{{ chf(unit(line) * (Number(line.quantity) || 0)) }}</Badge>
							<Badge v-if="failed(index)" variant="danger">Unvollständig</Badge>
						</span>
					</div>
				</EditableListItem>
				<p v-if="errors.lines" class="mt-16 text-md text-danger lg:text-lg">{{ errors.lines[0] }}</p>

				<div class="mt-24 flex">
					<button type="button" title="Position hinzufügen" class="block hover:text-teal" @click="editing = 'neu'">
						<IconPlus size="lg" class="block" />
					</button>
				</div>
			</Collapsible>

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

		<PositionDialog
			v-if="editing !== null"
			:line="editing === 'neu' ? blank() : lines[editing]"
			:options="options"
			:variants="variants"
			:free="FREE"
			:errors="editing === 'neu' ? {} : lineErrors(editing)"
			:removable="editing !== 'neu'"
			@save="keep"
			@remove="drop"
			@close="editing = null"
		/>
	</ArticleText>
</template>
