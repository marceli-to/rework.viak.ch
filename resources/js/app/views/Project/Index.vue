<script setup>
import { onMounted, ref } from 'vue';
import { fetchProjects, saveProjectOrder } from '@/api/projects';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import Badge from '@/components/ui/Badge.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Seiteninhalte → Vorhaben* ([[04-content]]), drawn as *Team* is: the header
 * with its `+`, then a row per Vorhaben, **dragged into the order the
 * homepage's tiles show**. The title in the first third, the tile's line after
 * it, and how many courses the page lists as a badge.
 */
const items = ref([]);
const loading = ref(true);
const error = ref(null);

const { dragging, handlers } = useSortable(items, async (list) => {
	try {
		await saveProjectOrder(list.map((item) => item.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

onMounted(async () => {
	try {
		items.value = await fetchProjects();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Vorhaben" :create="{ name: 'content.project.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />
		<NoResults v-else-if="!items.length">Noch keine Vorhaben erfasst.</NoResults>

		<EditableListItem
			v-for="(item, index) in items"
			:key="item.uuid"
			:edit="{ name: 'content.project.edit', params: { uuid: item.uuid } }"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
			wide
		>
			<div class="col-span-12 sm:col-span-4">{{ item.title }}</div>
			<div class="col-span-12 pr-40 sm:col-span-8">
				{{ item.teaser }}
				<span class="flex flex-wrap gap-8" :class="{ 'mt-8': item.teaser }">
					<Badge>{{ item.courses.length }} {{ item.courses.length === 1 ? 'Kurs' : 'Kurse' }}</Badge>
					<Badge v-if="!item.publish">nicht publiziert</Badge>
				</span>
			</div>
		</EditableListItem>
	</section>
</template>
