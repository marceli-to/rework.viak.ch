<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchNewsList, saveNewsOrder } from '@/api/news';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import Badge from '@/components/ui/Badge.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Seiteninhalte → News* ([[News]]): the Team list's pattern, a row per item,
 * **dragged into the order the homepage shows**. The first two published
 * are on the homepage and say so; a link whose page is not on the site says
 * that it shows no arrow.
 */
const items = ref([]);
const loading = ref(true);
const error = ref(null);

const shown = computed(() => items.value.filter((item) => item.publish).slice(0, 2).map((item) => item.uuid));

const { dragging, handlers } = useSortable(items, async (list) => {
	try {
		await saveNewsOrder(list.map((item) => item.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

onMounted(async () => {
	try {
		items.value = await fetchNewsList();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="News" :create="{ name: 'content.news.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />
		<NoResults v-else-if="!items.length">Noch keine News erfasst.</NoResults>

		<EditableListItem
			v-for="(item, index) in items"
			:key="item.uuid"
			:edit="{ name: 'content.news.edit', params: { uuid: item.uuid } }"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
			wide
		>
			<!-- The title and its badges on the left, the text, where to on the right (Marcel, 2026-11-17). -->
			<div class="col-span-12 sm:col-span-4">
				{{ item.title }}
				<span class="mt-8 flex flex-wrap gap-8">
					<Badge v-if="shown.includes(item.uuid)" variant="solid">auf der Startseite</Badge>
					<Badge v-if="item.link && !item.url">Seite nicht auf der Website, ohne Pfeil</Badge>
					<Badge v-if="!item.publish" variant="warning">nicht publiziert</Badge>
				</span>
			</div>
			<!-- The text in the middle, one line, cut with an ellipsis; whole in its title. -->
			<div class="col-span-12 min-w-0 truncate text-gray-600 max-sm:mt-8 sm:col-span-5" :title="item.text">{{ item.text }}</div>
			<div class="col-span-12 pr-40 max-sm:mt-8 sm:col-span-3">{{ item.link_label ?? 'Kein Link' }}</div>
		</EditableListItem>
	</section>
</template>
