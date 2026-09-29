<script setup>
/**
 * *Keine Kurse gefunden*, *Es sind noch keine Dokumente vorhanden.*: an empty
 * list saying so. Legacy's `.no-results` (`typo/_helpers.scss`): italic, 16px
 * above and 32 from sm, 14 / 16 / 18px. One component so every empty list
 * says it the same way (Marcel, 2026-09-29).
 */
</script>

<template>
	<p class="mt-16 text-md italic sm:mt-32 sm:text-lg lg:text-xl"><slot /></p>
</template>
