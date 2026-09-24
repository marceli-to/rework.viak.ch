<script setup>
import { ref } from 'vue';

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
 * `dimmed` is legacy's `is-hidden` — an unpublished course, every child at 80 %
 * opacity. The `action` slot is legacy's too: something absolutely placed
 * against the block, which on *Kurse* is the course's edit pencil.
 */
const props = defineProps({
	expanded: { type: Boolean, default: false },
	dimmed: { type: Boolean, default: false },
});

const open = ref(props.expanded);
</script>

<template>
	<section class="relative mb-64 border-t border-gray-600 sm:border-t-2 sm:text-lg lg:text-xl" :class="{ '[&_*]:opacity-80': dimmed }">
		<h2 class="leading-none font-bold text-gray-600">
			<button
				type="button"
				class="relative block w-full pt-16 pb-6 text-left leading-none transition-colors hover:text-gray-400"
				:aria-expanded="open"
				@click="open = !open"
			>
				<slot name="title" />
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
