<script setup>
import { RouterLink } from 'vue-router';
import IconPlus from '@/components/icons/Plus.vue';

/**
 * Legacy's `content-list-header` on the dashboard: three `span-4` columns — the
 * title in bold teal, a `+` to create, and the search pushed to the right —
 * with no rule of its own (`components/lists/_global.scss`).
 *
 * `tag`: a page with more than one list (*Software*) heads the others `h2`.
 * The `create` slot stands in for the link where the `+` opens something.
 */
defineProps({
	title: { type: String, required: true },
	create: { type: [String, Object], default: null },
	tag: { type: String, default: 'h1' },
});
</script>

<template>
	<div class="grid grid-cols-12 items-start gap-x-16 lg:gap-x-40">
		<component :is="tag" class="col-span-4 font-bold text-teal">{{ title }}</component>
		<div class="col-span-4">
			<RouterLink v-if="create" :to="create" class="mt-4 block size-16 hover:text-teal" title="Neu erfassen">
				<IconPlus size="lg" class="block" />
			</RouterLink>
			<!-- A `+` that asks something first: *Bestellungen* picks the customer in a lightbox. -->
			<slot v-else name="create" />
		</div>
		<div class="col-span-4 flex justify-end">
			<!-- `.search-container` carries the header's height: 12px under the
			     field, 24 from `bp-md` — the list header is 57px on production. -->
			<div class="mb-12 w-219 lg:mb-24"><slot name="search" /></div>
		</div>
	</div>
</template>
