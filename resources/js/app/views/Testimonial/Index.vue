<script setup>
import { onMounted, ref } from 'vue';
import { fetchTestimonials, orderTestimonials } from '@/api/testimonials';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import ListHeader from '@/components/list/ListHeader.vue';
import StackedListItem from '@/components/list/StackedListItem.vue';

/**
 * *Seiteninhalte → Testimonials* ([[07-dashboard]]). New — legacy has no such
 * screen — so it is drawn as legacy draws its other content lists, News and
 * Team: the header with its `+`, then a stacked row per item, dragged into
 * order as Team's are. The order is the one the site shows them in.
 */
const items = ref([]);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
	try {
		items.value = await fetchTestimonials();
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});

const { dragging, handlers } = useSortable(items, async (list) => {
	try {
		await orderTestimonials(list.map((item) => item.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});
</script>

<template>
	<section>
		<ListHeader title="Testimonials" :create="{ name: 'content.testimonial.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<p v-else-if="loading" class="mt-32">Wird geladen …</p>
		<p v-else-if="!items.length" class="mt-32">Noch keine Testimonials erfasst.</p>

		<StackedListItem
			v-for="(item, index) in items"
			:key="item.uuid"
			:edit="{ name: 'content.testimonial.edit', params: { uuid: item.uuid } }"
			:dimmed="!item.publish"
			draggable="true"
			class="cursor-grab"
			:class="{ 'opacity-40': dragging === index }"
			v-bind="handlers(index)"
		>
			{{ item.name }}
			<span v-if="item.context" class="text-lg">({{ item.context }})</span>
			<span v-if="!item.publish" class="ml-4 text-lg">nicht publiziert</span>
		</StackedListItem>
	</section>
</template>
