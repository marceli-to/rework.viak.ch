<script setup>
import { onMounted, computed } from 'vue';
import { useEventStore } from '@/stores/events';
import StateBadge from '@/components/StateBadge.vue';

const store = useEventStore();

onMounted(() => store.load());

const formatDate = (value) =>
	value ? new Date(value).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '–';

/** Cancelling is terminal server-side, so the UI stops offering transitions. */
const transitionsFor = (event) =>
	event.state === 'cancelled'
		? []
		: [
				{ state: 'confirmed', label: 'Bestätigen' },
				{ state: 'closed', label: 'Schliessen' },
				{ state: 'cancelled', label: 'Absagen' },
			].filter((option) => option.state !== event.state);
</script>

<template>
	<section>
		<header class="mb-24 flex items-center justify-between">
			<div>
				<h1 class="text-3xl font-semibold tracking-tight text-black">Kursdaten</h1>
				<p class="mt-4 text-md text-gray-600">{{ store.items.length }} Termine</p>
			</div>

			<button
				type="button"
				class="border border-gray-400 px-12 py-6 text-md text-gray-600 transition hover:border-teal hover:text-teal"
				@click="store.togglePast()"
			>
				{{ store.showPast ? 'Kommende zeigen' : 'Vergangene zeigen' }}
			</button>
		</header>

		<p v-if="store.error" class="mb-16 bg-danger/10 px-16 py-8 text-md text-danger">{{ store.error }}</p>
		<p v-if="store.loading" class="text-md text-gray-600">Wird geladen …</p>

		<table v-else-if="store.items.length" class="w-full border-collapse text-md">
			<thead>
				<tr class="border-b border-gray-400 text-left text-xs uppercase tracking-wider text-gray-400">
					<th class="py-8 pr-16 font-semibold">Datum</th>
					<th class="py-8 pr-16 font-semibold">Kurs</th>
					<th class="py-8 pr-16 font-semibold">Plätze</th>
					<th class="py-8 pr-16 font-semibold">Status</th>
					<th class="py-8 font-semibold"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="event in store.items" :key="event.uuid" class="border-b border-gray-400/70 align-top">
					<td class="py-12 pr-16 whitespace-nowrap text-gray-600">{{ formatDate(event.date) }}</td>
					<td class="py-12 pr-16">
						<span class="font-medium text-black">{{ event.course?.title?.de ?? '—' }}</span>
						<span v-if="event.free_of_charge" class="ml-8 text-xs text-gray-400">kostenlos</span>
					</td>
					<td class="py-12 pr-16 text-gray-600">{{ event.min_participants }}–{{ event.max_participants }}</td>
					<td class="py-12 pr-16"><StateBadge :state="event.state" /></td>
					<td class="py-12 text-right whitespace-nowrap">
						<button
							v-for="option in transitionsFor(event)"
							:key="option.state"
							type="button"
							class="ml-8 text-xs font-semibold text-teal hover:underline"
							@click="store.changeState(event.uuid, option.state)"
						>
							{{ option.label }}
						</button>
					</td>
				</tr>
			</tbody>
		</table>

		<p v-else class="text-md text-gray-600">Keine Termine gefunden.</p>
	</section>
</template>
