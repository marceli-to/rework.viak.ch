<script setup>
import ListHeader from '@/components/list/ListHeader.vue';
import ImageSection from '@/components/media/ImageSection.vue';

/**
 * *Seiteninhalte → Startseite* ([[Page]]): the intro's slider, legacy's home
 * hero. The images are the course form's image section on the homepage's
 * `Page`, dragged into the slider's order; every action saves at once. The
 * one marked *OpenGraph* is the page's `og:image` and stays out of the slider.
 * The intro's copy is legacy's, in Blade, so there is nothing else to edit.
 */
</script>

<template>
	<section>
		<ListHeader title="Startseite" />
		<div class="mt-32">
			<ImageSection record="home" owner="pages" />
		</div>
	</section>
</template>
