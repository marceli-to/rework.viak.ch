<script setup>
import { dismiss, toasts } from '@/composables/useToast';

/**
 * `resources/views/components/ui/toast.blade.php` in its live mode: fixed at
 * the top, on the right edge of the column from sm, green for success and red
 * for an error, bold white, gone on a click or after four seconds.
 */
const FILL = { success: 'bg-success', error: 'bg-danger', info: 'bg-gray-600' };
const EDGE = { success: 'border-success', error: 'border-danger', info: 'border-gray-600' };
</script>

<template>
	<div
		v-for="item in toasts.slice(-1)"
		:key="item.id"
		role="status"
		class="fixed top-16 left-16 z-[1001] w-[calc(100%-32px)] cursor-pointer text-lg text-white sm:left-auto sm:w-auto sm:max-w-360 sm:right-[calc((100%-1100px)/2+16px)] lg:max-w-480 lg:text-xl"
		:class="FILL[item.tone] ?? FILL.success"
		@click="dismiss(item.id)"
	>
		<div class="flex items-center border p-8 sm:px-16" :class="EDGE[item.tone] ?? EDGE.success">
			<div class="font-bold">{{ item.title }}</div>
		</div>
	</div>
</template>
