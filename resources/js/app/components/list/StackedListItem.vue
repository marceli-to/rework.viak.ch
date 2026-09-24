<script setup>
import { RouterLink } from 'vue-router';
import IconEdit from '@/components/icons/Edit.vue';

/**
 * Legacy's `stacked-list-item` — one row of its content lists (News, Team),
 * measured on its dashboard on 2026-09-24 (`components/lists/_stacked.scss`).
 *
 * A 1px black rule, 16px above it and 8 inside, 32 and 16 from sm; 18px at a
 * line height of 1.5, then 1.4; the content in an 8-of-12 column and the
 * pencil on the right, 13px below the rule. `dimmed` is an unpublished row.
 */
defineProps({
	edit: { type: [String, Object], required: true },
	dimmed: { type: Boolean, default: false },
});
</script>

<template>
	<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl" :class="{ 'text-gray-400': dimmed }">
		<RouterLink :to="edit" title="Bearbeiten" class="absolute top-12 right-0 z-10 block size-18 text-black hover:text-teal">
			<IconEdit class="block" />
		</RouterLink>
		<div class="grid grid-cols-12 gap-x-16 lg:gap-x-40">
			<div class="col-span-10 sm:col-span-8"><slot /></div>
		</div>
	</article>
</template>
