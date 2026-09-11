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
		<header class="mb-6 flex items-center justify-between">
			<div>
				<h1 class="text-2xl font-semibold tracking-tight text-ink">Kursdaten</h1>
				<p class="mt-1 text-sm text-muted">{{ store.items.length }} Termine</p>
			</div>

			<button
				type="button"
				class="border border-line px-3 py-1.5 text-sm text-muted transition hover:border-teal hover:text-teal-dark"
				@click="store.togglePast()"
			>
				{{ store.showPast ? 'Kommende zeigen' : 'Vergangene zeigen' }}
			</button>
		</header>

		<p v-if="store.error" class="mb-4 bg-danger/10 px-4 py-2 text-sm text-danger">{{ store.error }}</p>
		<p v-if="store.loading" class="text-sm text-muted">Wird geladen …</p>

		<table v-else-if="store.items.length" class="w-full border-collapse text-sm">
			<thead>
				<tr class="border-b border-line text-left text-xs uppercase tracking-wider text-faint">
					<th class="py-2 pr-4 font-semibold">Datum</th>
					<th class="py-2 pr-4 font-semibold">Kurs</th>
					<th class="py-2 pr-4 font-semibold">Plätze</th>
					<th class="py-2 pr-4 font-semibold">Status</th>
					<th class="py-2 font-semibold"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="event in store.items" :key="event.uuid" class="border-b border-line/70 align-top">
					<td class="py-3 pr-4 whitespace-nowrap text-muted">{{ formatDate(event.date) }}</td>
					<td class="py-3 pr-4">
						<span class="font-medium text-ink">{{ event.course?.title?.de ?? '—' }}</span>
						<span v-if="event.free_of_charge" class="ml-2 text-xs text-faint">kostenlos</span>
					</td>
					<td class="py-3 pr-4 text-muted">{{ event.min_participants }}–{{ event.max_participants }}</td>
					<td class="py-3 pr-4"><StateBadge :state="event.state" /></td>
					<td class="py-3 text-right whitespace-nowrap">
						<button
							v-for="option in transitionsFor(event)"
							:key="option.state"
							type="button"
							class="ml-2 text-xs font-semibold text-teal-dark hover:underline"
							@click="store.changeState(event.uuid, option.state)"
						>
							{{ option.label }}
						</button>
					</td>
				</tr>
			</tbody>
		</table>

		<p v-else class="text-sm text-muted">Keine Termine gefunden.</p>
	</section>
</template>
