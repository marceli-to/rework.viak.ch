<script setup>
import { onBeforeUnmount, ref } from 'vue';

/**
 * *Wird geladen …*, shown only once loading has taken 200ms. Most loads finish
 * sooner, and the line would only flash up before the content replaced it.
 * The bar across the top ([[07-dashboard]]) shows that something is happening
 * in the meantime.
 */
const shown = ref(false);
const timer = setTimeout(() => (shown.value = true), 200);
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
	<p v-if="shown">Wird geladen …</p>
</template>
