<script setup>
import { onMounted, ref } from 'vue';
import { fetchTeamMembers, saveTeamMemberOrder } from '@/api/teamMembers';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import Badge from '@/components/ui/Badge.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import NoResults from '@/components/ui/NoResults.vue';

/**
 * *Seiteninhalte → Team* — legacy's `views/team_member/Index.vue`
 * ([[07-dashboard]]): the header with its `+`, then a row per person, **dragged
 * into the order *Über uns* shows them**. Name in the first third, what they do
 * after it.
 */
const items = ref([]);
const loading = ref(true);
const error = ref(null);

const { dragging, handlers } = useSortable(items, async (list) => {
	try {
		await saveTeamMemberOrder(list.map((item) => item.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

onMounted(async () => {
	try {
		items.value = await fetchTeamMembers();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});
</script>

<template>
	<section>
		<ListHeader title="Team" :create="{ name: 'content.team-member.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />
		<NoResults v-else-if="!items.length">Noch keine Teammitglieder erfasst.</NoResults>

		<EditableListItem
			v-for="(item, index) in items"
			:key="item.uuid"
			:edit="{ name: 'content.team-member.edit', params: { uuid: item.uuid } }"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
			wide
		>
			<div class="col-span-12 sm:col-span-4">{{ item.name }}</div>
			<div class="col-span-12 pr-40 sm:col-span-8">
				{{ item.role }}
				<span v-if="!item.publish" :class="{ 'mt-8 block': item.role }"><Badge>nicht publiziert</Badge></span>
			</div>
		</EditableListItem>
	</section>
</template>
