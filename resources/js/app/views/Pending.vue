<script setup>
import { useRoute } from 'vue-router';

/**
 * Where the menu points until a screen is built ([[07-dashboard]], build
 * order). Legacy's menu is drawn whole so the layout is true from the first
 * screen on; each entry lands here until its own view replaces it.
 */
const route = useRoute();
</script>

<template>
	<section>
		<h1 class="font-bold text-teal">{{ route.meta.title }}</h1>
		<p class="mt-32 sm:text-lg lg:text-xl">Dieser Bereich ist noch nicht umgesetzt.</p>
	</section>
</template>
