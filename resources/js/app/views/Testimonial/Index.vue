<script setup>
import { onMounted, ref } from 'vue';
import { fetchTestimonials } from '@/api/testimonials';
import Badge from '@/components/ui/Badge.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';

/**
 * *Seiteninhalte → Testimonials* ([[07-dashboard]]). New — legacy has no such
 * screen — so it is drawn as legacy draws its other content lists, News and
 * Team: the header with its `+`, then a stacked row per item.
 *
 * **Not dragged into order here.** Each page orders its own testimonials,
 * in the picker that places them there, so the list has no order of its own.
 *
 * What it is about under the title, in the header's first third. The rest
 * of the row goes to the quote and who said it, with where it is used at the
 * end, only as wide as it needs (at most 240px, so a long course title wraps).
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
</script>

<template>
	<section>
		<ListHeader title="Testimonials" :create="{ name: 'content.testimonial.create' }" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />
		<p v-else-if="!items.length" class="mt-32">Noch keine Testimonials erfasst.</p>

		<EditableListItem
			v-for="item in items"
			:key="item.uuid"
			:edit="{ name: 'content.testimonial.edit', params: { uuid: item.uuid } }"
			:dimmed="!item.publish"
			wide
		>
			<div class="col-span-12 text-lg max-sm:pr-40 sm:col-span-4">{{ item.subject_label }}</div>

			<!-- Clear of the pencil, which sits over this column's right edge. -->
			<div class="col-span-12 max-sm:mt-8 sm:col-span-8 sm:flex sm:gap-x-16 sm:pr-40 lg:gap-x-40">
				<div class="min-w-0 flex-1">
					<p class="line-clamp-3" :title="item.quote">„{{ item.quote }}“</p>
					<p class="text-lg">
						{{ item.name }}<template v-if="item.context"> ({{ item.context }})</template>
						<Badge v-if="!item.publish" class="ml-8">nicht publiziert</Badge>
					</p>
				</div>

				<div class="shrink-0 text-lg max-sm:mt-8 sm:max-w-240">
					<template v-if="item.placements.length">
						Verwendet auf:
						<span v-for="place in item.placements" :key="`${place.type}-${place.uuid}`" class="block">{{ place.label }}</span>
					</template>
					<Badge v-else>Noch nicht verwendet</Badge>
				</div>
			</div>
		</EditableListItem>
	</section>
</template>
