<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchSettings } from '@/api/settings';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import IconPlus from '@/components/icons/Plus.vue';
import NoResults from '@/components/ui/NoResults.vue';
import { KINDS, usage } from './kinds';

/**
 * *Einstellungen* — legacy's `views/setting/Index.vue` ([[07-dashboard]],
 * step 6): one collapsible per list, each row a name, a `+` under the list
 * to add one. The list a form came back from is open (`?liste=`), as
 * legacy's `:type` param opened it.
 *
 * Each row says where it is used, which is also why its form may refuse to
 * delete it. Legacy's second column, the English name, is not shown: the
 * dashboard writes German (#6).
 */
const route = useRoute();

const lists = ref(null);
const error = ref(null);
const open = computed(() => String(route.query.liste ?? ''));

const label = (kind, item) => (kind === 'locations' ? item.description : item.title);

onMounted(async () => {
	try {
		lists.value = await fetchSettings();
	} catch (problem) {
		error.value = problem.message;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Einstellungen" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="!lists" class="mt-32" />

		<!-- Legacy's `.collapsible-container`, `mt-12x md:mt-16x`: 24px, 32 from lg. Only *Kurse* has the tight 6px. -->
		<div v-else class="mt-24 lg:mt-32">
			<Collapsible v-for="(kind, key) in KINDS" :key="key" :expanded="open === key" :count="lists[key].length">
				<template #title>{{ kind.title }}</template>

				<EditableListItem
					v-for="item in lists[key]"
					:key="item.uuid"
					:edit="{ name: 'setting.edit', params: { kind: key, uuid: item.uuid } }"
					:dimmed="key === 'locations' && !item.publish"
					wide
				>
					<div class="col-span-12 sm:col-span-4">{{ label(key, item) }}</div>
					<div class="col-span-12 pr-40 max-sm:mt-8 sm:col-span-8"><Badge>{{ usage(key, item.usage) }}</Badge></div>
				</EditableListItem>
				<NoResults v-if="!lists[key].length">Noch keine erfasst.</NoResults>

				<div class="mt-24 flex">
					<RouterLink :to="{ name: 'setting.create', params: { kind: key } }" :title="`${kind.noun} hinzufügen`" class="block hover:text-teal">
						<IconPlus size="lg" class="block" />
					</RouterLink>
				</div>
			</Collapsible>
		</div>
	</section>
</template>
