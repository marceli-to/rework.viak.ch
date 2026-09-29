<script setup>
import { RouterLink } from 'vue-router';
import IconArrowLeft from '@/components/icons/ArrowLeft.vue';
import { goBack } from '@/router';

/**
 * `resources/views/components/ui/back-link.blade.php` — *Zurück* over a left
 * arrow. **Back to where the admin came from** ([[goBack]]); `to` is where
 * it goes when the page was opened directly, and the link's address.
 */
const props = defineProps({
	to: { type: [String, Object], required: true },
	label: { type: String, default: 'Zurück' },
});

// A click with a modifier opens `to` as a link would.
function back(event) {
	if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
	event.preventDefault();
	goBack(props.to);
}
</script>

<template>
	<div class="sm:mt-20 lg:mt-40">
		<RouterLink v-slot="{ href }" :to="to" custom>
			<a :href="href" :title="label" class="inline-block text-left transition-colors hover:text-teal" @click="back">
				<span class="mb-4 block">{{ label }}</span>
				<IconArrowLeft />
			</a>
		</RouterLink>
	</div>
</template>
