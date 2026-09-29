<script setup>
import { computed } from 'vue';
import Button from '@/components/ui/Button.vue';

/**
 * One course document: `resources/views/components/row/file.blade.php`, which
 * is legacy's `shared/modules/files/components/ListItem.vue`. Keep the two in
 * step. Four columns: the name (the caption with the file name where there is
 * one, cut at 35 characters), when it was uploaded, its size, and *Download*
 * over whatever the `action` slot holds (the event page's *Löschen*).
 */
const props = defineProps({ file: { type: Object, required: true } });

const name = computed(() => {
	const text = props.file.caption ? `${props.file.caption} (${props.file.name})` : props.file.name;
	return text.length > 35 ? `${text.slice(0, 35)}...` : text;
});

// Legacy's `fileSize` filter: base 1000, two decimals, trailing zeros dropped (*1.54 MB*).
const size = computed(() => {
	const bytes = props.file.size;
	if (!bytes) return '0 Bytes';
	const units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
	const step = Math.min(Math.floor(Math.log(bytes) / Math.log(1000)), units.length - 1);
	return `${parseFloat((bytes / 1000 ** step).toFixed(2))} ${units[step]}`;
});
</script>

<template>
	<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="sm:col-span-4" :title="file.name">{{ name }}</div>
			<div class="sm:col-span-3">{{ file.uploaded_at }}</div>
			<div class="sm:col-span-2">{{ size }}</div>
			<div class="mt-24 sm:col-span-3 sm:mt-0 sm:flex sm:justify-end">
				<div class="[&>*+*]:mt-8">
					<Button :href="file.url" target="_blank" :title="file.name">Download</Button>
					<slot name="action" />
				</div>
			</div>
		</div>
	</article>
</template>
