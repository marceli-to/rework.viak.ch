<script setup>
import { RouterLink } from 'vue-router';
import IconArrowLeft from '@/components/icons/ArrowLeft.vue';

/** `resources/views/components/ui/back-link.blade.php` — *Zurück* over a left arrow. */
defineProps({
	to: { type: [String, Object], required: true },
	label: { type: String, default: 'Zurück' },
});
</script>

<template>
	<div class="sm:mt-20 lg:mt-40">
		<RouterLink :to="to" :title="label" class="inline-block text-left transition-colors hover:text-teal">
			<span class="mb-4 block">{{ label }}</span>
			<IconArrowLeft />
		</RouterLink>
	</div>
</template>
