<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchSettings } from '@/api/settings';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import SettingList from './List.vue';
import { KINDS } from './kinds';

/**
 * *Einstellungen* — legacy's `views/setting/Index.vue` ([[07-dashboard]],
 * step 6): one collapsible per list ([[List]]). The list a form came back
 * from is open (`?liste=`), as legacy's `:type` param opened it. The lists
 * licences hang off are on *Software* instead ([[05-licences]]).
 *
 * Each row says where it is used, which is also why its form may refuse to
 * delete it. Legacy's second column, the English name, is not shown: the
 * dashboard writes German (#6).
 */
const route = useRoute();

const lists = ref(null);
const error = ref(null);
const open = computed(() => String(route.query.liste ?? ''));
const kinds = Object.keys(KINDS).filter((key) => !KINDS[key].home);

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
			<SettingList v-for="key in kinds" :key="key" :kind-key="key" :items="lists[key]" :expanded="open === key" />
		</div>
	</section>
</template>
