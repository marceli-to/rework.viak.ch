<script setup>
import { ref, watch } from 'vue';
import { fetchCustomers } from '@/api/customers';
import Button from '@/components/ui/Button.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import NoResults from '@/components/ui/NoResults.vue';
import SearchField from '@/components/list/SearchField.vue';

/**
 * A customer, searched on the server: *Teilnehmer hinzufügen*'s lightbox
 * ([[EventPageController::book]]), with the button's label as a prop.
 */
defineProps({
	title: { type: String, required: true },
	action: { type: String, default: 'Auswählen' },
});

const emit = defineEmits(['pick', 'close']);

const search = ref('');
const found = ref([]);
const searching = ref(false);

watch(search, async (value) => {
	if (!value.trim()) {
		found.value = [];
		return;
	}
	searching.value = true;
	try {
		const reply = await fetchCustomers({ search: value });
		if (value === search.value) found.value = reply.data;
	} finally {
		searching.value = false;
	}
});
</script>

<template>
	<Lightbox :title="title" @close="emit('close')">
		<SearchField v-model="search" />
		<NoResults v-if="search && !searching && !found.length">Keine Kunden gefunden.</NoResults>
		<!-- Only the hits scroll, not the box: the title and the search stay put, and 16px keep
		     the buttons off the scrollbar (Marcel, 2026-10-08). -->
		<ul v-if="found.length" class="mt-16 max-h-[60vh] overflow-y-auto pr-16 text-lg">
			<li v-for="customer in found" :key="customer.uuid" class="flex items-center justify-between gap-16 border-b border-gray-400 py-8">
				<span class="min-w-0">{{ customer.name }}<template v-if="customer.city">, {{ customer.city }}</template><br /><span class="text-md text-gray-600">{{ customer.email }}</span></span>
				<Button class="shrink-0" @click="emit('pick', customer)">{{ action }}</Button>
			</li>
		</ul>
	</Lightbox>
</template>
