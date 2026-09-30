<script setup>
import { provide, ref } from 'vue';
import Badge from './Badge.vue';

/**
 * `resources/views/components/ui/collapsible.blade.php` in legacy's dashboard
 * variant — `.collapsible` inside `.collapsible-container.is-course-events`,
 * measured on the local legacy dashboard on 2026-09-24.
 *
 * The same `#505050` rule, 2px from sm, and the same 12×9 CSS triangle as the
 * site's, but a **tighter heading**: 16px above and 6px below at a line height
 * of 1 (40px in all, where the site's is 58), no gap under it, and the triangle
 * 20px down rather than centred. 64px to the next block, as on the site.
 *
 * The label is 14/16/18px, as legacy's and the site's are; left to inherit it
 * was 16px on a phone (measured against legacy at 390px, 2026-09-30).
 *
 * `dimmed` is legacy's `is-hidden` — an unpublished course, every child at 80 %
 * opacity. The `action` slot is legacy's too: something absolutely placed
 * against the block, which on *Kurse* is the course's edit pencil.
 */
const props = defineProps({
	expanded: { type: Boolean, default: false },
	dimmed: { type: Boolean, default: false },
	// Legacy's `is-invalid`: the title goes red when a field inside failed.
	invalid: { type: Boolean, default: false },
	// How many are inside, as a solid badge beside the title, open or shut
	// (Marcel, 2026-09-29) — the Blade twin's `count`. Nothing for a zero.
	count: { type: Number, default: 0 },
});

const open = ref(props.expanded);

// What is inside may need to know when it is shown — a textarea sizes itself
// to its text and cannot while it is hidden ([[Textarea]]).
provide('collapsibleOpen', open);
</script>

<template>
	<section class="relative mb-64 border-t border-gray-600 sm:border-t-2 sm:text-lg lg:text-xl" :class="{ '[&_*]:opacity-80': dimmed }">
		<h2 class="text-md leading-none font-bold sm:text-lg lg:text-xl" :class="invalid ? 'text-danger' : 'text-gray-600'">
			<button
				type="button"
				class="relative block w-full pt-16 pb-6 text-left leading-none transition-colors hover:text-gray-400"
				:aria-expanded="open"
				@click="open = !open"
			>
				<slot name="title" />
				<!-- `-my-4`: the badge is 23px high on an 18px line, and without it the heading grew
				     from legacy's 40px to 45 wherever there was a count (measured 2026-09-30). -->
				<Badge v-if="count" variant="solid" class="-my-4 ml-12">{{ count }}</Badge>
				<span
					aria-hidden="true"
					class="absolute top-20 right-0 block size-0 border-x-[6px] border-x-transparent"
					:class="open ? 'border-b-[9px] border-b-current' : 'border-t-[9px] border-t-current'"
				/>
			</button>
		</h2>

		<slot name="action" />

		<div v-show="open"><slot /></div>
	</section>
</template>
