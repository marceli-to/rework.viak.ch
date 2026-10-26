<script setup>
import { onMounted, ref } from 'vue';
import { createMcpToken, fetchMcpTokens, revokeMcpToken } from '@/api/mcpTokens';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Field from '@/components/form/Field.vue';
import NoResults from '@/components/ui/NoResults.vue';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';

/**
 * *MCP-Zugang* under *Mein Profil* ([[17-mcp]]): the admin's own tokens for
 * the MCP server, one per AI client, each revocable ([[McpTokenController]]).
 *
 * Shut, with the count, as the dashboard's lists are. A new token is shown
 * once, in a read-only field under the list, with the address to enter
 * beside it; Sanctum keeps only its hash, so there is no showing it again.
 *
 * And the two put together as Claude Code's command, to copy and paste into
 * a terminal: `--scope user` so it answers in every folder, not only the one
 * it was added in. Claude Code takes a bearer token; claude.ai and Claude
 * Desktop's connectors want OAuth, which is not built (#65).
 */
const tokens = ref([]);
const name = ref('');
const error = ref(null);
const created = ref(null);
const busy = ref(false);

const endpoint = `${window.location.origin}/mcp`;

const command = (token) => `claude mcp add --scope user --transport http viak ${endpoint} --header "Authorization: Bearer ${token}"`;

async function copyCommand() {
	try {
		await navigator.clipboard.writeText(command(created.value));
		toast('Befehl kopiert');
	} catch {
		toast('Kopieren nicht möglich, bitte Token und Adresse von Hand eintragen', 'error');
	}
}

onMounted(async () => {
	tokens.value = await fetchMcpTokens();
});

async function create() {
	busy.value = true;
	error.value = null;
	try {
		const { data, token } = await createMcpToken(name.value);
		tokens.value.unshift(data);
		created.value = token;
		name.value = '';
	} catch (problem) {
		error.value = problem.errors?.name?.[0] ?? problem.message;
	} finally {
		busy.value = false;
	}
}

async function revoke(token) {
	if (!(await confirm('Bitte Widerruf bestätigen!', `«${token.name}» hat danach keinen Zugang mehr.`))) return;
	await revokeMcpToken(token.id);
	tokens.value = tokens.value.filter((item) => item.id !== token.id);
	toast('Zugang widerrufen');
}
</script>

<template>
	<div class="mt-48 lg:mt-64">
		<Collapsible :count="tokens.length">
			<template #title>MCP-Zugang</template>

			<p class="mt-16 text-md sm:text-lg lg:text-xl">
				Ein Token pro KI-Programm (zum Beispiel Claude auf dem Büro-Laptop). Es kann nur lesen: Veranstaltungen, Buchungen, offene Rechnungen und Lizenzbestellungen.
			</p>

			<article v-for="token in tokens" :key="token.id" class="mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
				<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
					<div class="font-bold sm:col-span-4">{{ token.name }}</div>
					<div class="sm:col-span-3">erstellt {{ token.created_at }}</div>
					<div class="sm:col-span-3">{{ token.last_used_at ? `zuletzt ${token.last_used_at}` : 'noch nie benutzt' }}</div>
					<div class="mt-16 sm:col-span-2 sm:mt-0 sm:flex sm:justify-end">
						<Button variant="danger" @click="revoke(token)">Widerrufen</Button>
					</div>
				</div>
			</article>
			<NoResults v-if="!tokens.length">Noch kein Token erstellt.</NoResults>

			<!-- Inside the profile's form: Enter here creates the token, it does not save the profile. -->
			<div class="mt-32 lg:mt-48" @keydown.enter.prevent="name.trim() && create()">
				<Field v-model="name" label="Bezeichnung" :error="error" hint="Wofür das Token ist, zum Beispiel «Claude, Laptop Büro»." />
				<Button class="w-full" :disabled="busy || !name.trim()" @click="create">Token erstellen</Button>
			</div>

			<div v-if="created" class="mt-32 lg:mt-48">
				<Field :model-value="created" label="Neues Token" readonly hint="Nur jetzt sichtbar. Kopieren und im KI-Programm eintragen; verloren heisst widerrufen und neu erstellen." />
				<Field :model-value="endpoint" label="Adresse des Servers" readonly />
				<Button class="mt-16 w-full" @click="copyCommand">Befehl für Claude Code kopieren</Button>
				<p class="mt-8 text-md text-gray-600 lg:text-lg">Im Terminal einfügen und ausführen. Danach kennt Claude Code den Server unter dem Namen «viak».</p>
			</div>
		</Collapsible>
	</div>
</template>
