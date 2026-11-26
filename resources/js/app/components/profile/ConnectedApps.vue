<script setup>
import { onMounted, ref } from 'vue';
import { disconnectApp, fetchConnectedApps } from '@/api/connectedApps';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import NoResults from '@/components/ui/NoResults.vue';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';

/**
 * *Verbundene Apps* under *Mein Profil* ([[17-mcp]]): the KI-Programme the
 * admin connected through OAuth, in practice the claude.ai connector
 * ([[ConnectedAppController]]). Nothing to create here: a connection is made
 * from claude.ai, by signing in there. Trennen ends it; the next use asks
 * for consent again.
 *
 * Laid out as [[McpTokens]] above it: shut, with the count.
 */
const apps = ref([]);

onMounted(async () => {
	apps.value = await fetchConnectedApps();
});

async function disconnect(app) {
	if (!(await confirm('Bitte Trennen bestätigen!', `«${app.name}» hat danach keinen Zugang mehr, bis du es neu verbindest.`))) return;
	await disconnectApp(app.id);
	apps.value = apps.value.filter((item) => item.id !== app.id);
	toast('Verbindung getrennt');
}
</script>

<template>
	<div class="mt-48 lg:mt-64">
		<Collapsible :count="apps.length">
			<template #title>Verbundene Apps</template>

			<p class="mt-16 text-md sm:text-lg lg:text-xl">
				KI-Programme, die du über die Anmeldung verbunden hast, zum Beispiel Claude im Browser. Verbunden wird in Claude selbst.
			</p>

			<article v-for="app in apps" :key="app.id" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="sm:col-span-10">
						<div class="font-bold">{{ app.name }}</div>
						<div>verbunden {{ app.connected_at }}, zuletzt {{ app.last_used_at }}</div>
					</div>
					<div class="mt-16 sm:col-span-2 sm:mt-0 sm:flex sm:justify-end">
						<Button variant="danger" @click="disconnect(app)">Trennen</Button>
					</div>
				</div>
			</article>
			<NoResults v-if="!apps.length">Keine App verbunden.</NoResults>
		</Collapsible>
	</div>
</template>
