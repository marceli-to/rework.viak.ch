<script setup>
import { computed, reactive, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Field from '@/components/form/Field.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Select from '@/components/form/Select.vue';

/**
 * One position of *Bestellung erfassen*, in a lightbox ([[05-licences]]): the
 * order is not saved yet, so a position has no page of its own as a
 * variant does. Edits a copy; *Übernehmen* hands it back, *Entfernen* drops
 * it. A variant with a minimum starts at it, not held to it.
 *
 * `options` and `variants` come from the form: the catalogue's select, and
 * each variant with its product. `errors` are the server's for this position.
 */
const props = defineProps({
	line: { type: Object, required: true },
	options: { type: Array, required: true },
	variants: { type: Map, required: true },
	free: { type: String, required: true },
	errors: { type: Object, default: () => ({}) },
	removable: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'remove', 'close']);

const draft = reactive({ ...props.line });
const local = ref({});

const variant = computed(() => props.variants.get(draft.choice));
const hosts = computed(() => (variant.value?.product.hosts ?? []).map((host) => ({ value: host, label: host })));

function choose(choice) {
	draft.choice = choice;
	draft.host = '';
	draft.quantity = Math.max(props.variants.get(choice)?.min_quantity ?? 1, 1);
}

const error = (key) => local.value[key] ?? props.errors[key];

// What the server would refuse, asked here, so a position never goes back to the list half filled.
function save() {
	const missing = {};
	if (!draft.choice) missing.variant = 'Bitte die Software wählen.';
	if (draft.choice === props.free && !String(draft.title).trim()) missing.title = 'Bitte eine Bezeichnung eingeben.';
	if (draft.choice === props.free && (draft.price === '' || Number.isNaN(Number(draft.price)))) missing.price = 'Bitte einen Preis eingeben.';
	if (hosts.value.length && !draft.host) missing.host = 'Bitte die Host-Software wählen.';
	if (!(Number(draft.quantity) >= 1)) missing.quantity = 'Bitte eine Anzahl ab 1 eingeben.';

	local.value = missing;
	if (!Object.keys(missing).length) emit('save', { ...draft });
}
</script>

<template>
	<Lightbox :title="line.choice ? 'Position bearbeiten' : 'Position hinzufügen'" @close="emit('close')">
		<form @submit.prevent="save">
			<Select :model-value="draft.choice" label="Lizenz" :options="options" placeholder="Bitte wählen" required :error="error('variant')" @update:model-value="choose" />

			<template v-if="draft.choice === free">
				<Field v-model="draft.title" label="Bezeichnung" required :error="error('title')" />
				<Field v-model="draft.price" label="Preis (CHF, netto)" required :error="error('price')" />
			</template>

			<Select v-if="hosts.length" v-model="draft.host" label="Host-Software" :options="hosts" placeholder="Bitte wählen" required :error="error('host')" />

			<Field
				v-model="draft.quantity"
				type="number"
				label="Anzahl"
				required
				:hint="variant?.min_quantity ? `Im Shop mindestens ${variant.min_quantity}.` : null"
				:error="error('quantity')"
			/>

			<div class="flex gap-16 max-sm:flex-col">
				<Button type="submit" class="sm:flex-1">Übernehmen</Button>
				<Button v-if="removable" variant="secondary" class="sm:flex-1" @click="emit('remove')">Entfernen</Button>
			</div>
		</form>
	</Lightbox>
</template>
