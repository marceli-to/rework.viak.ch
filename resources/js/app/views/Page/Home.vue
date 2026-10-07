<script setup>
import { onMounted, ref } from 'vue';
import { fetchPageTestimonials, savePageTestimonials } from '@/api/pages';
import { toast } from '@/composables/useToast';
import Button from '@/components/ui/Button.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import ImageSection from '@/components/media/ImageSection.vue';
import TestimonialPicker from '@/components/testimonial/TestimonialPicker.vue';

/**
 * *Seiteninhalte → Startseite* ([[Page]]): the intro's slider, legacy's home
 * hero, and the testimonials under *Kundenmeinungen* (the review's marker
 * 10). The images are the course form's image section on the homepage's
 * `Page`, dragged into the slider's order; every action saves at once. The
 * one marked *OpenGraph* is the page's `og:image` and stays out of the slider.
 * The testimonials are Firmenschulung's picker and save with the button. The
 * rest of the homepage's copy is in Blade, so there is nothing else to edit.
 */
const testimonials = ref([]);
const picked = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);

onMounted(async () => {
	try {
		const data = await fetchPageTestimonials('home');
		testimonials.value = data.testimonials;
		picked.value = data.picked;
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});

async function save() {
	saving.value = true;
	try {
		await savePageTestimonials('home', picked.value);
		toast('Gespeichert');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		saving.value = false;
	}
}
</script>

<template>
	<section>
		<ListHeader title="Startseite" />
		<div class="mt-32">
			<ImageSection record="home" owner="pages" />
		</div>

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<template v-else>
			<TestimonialPicker v-model="picked" :testimonials="testimonials" class="mt-32">
				<template #title><h2 class="text-lg font-bold sm:text-xl">Kundenmeinungen</h2></template>
			</TestimonialPicker>
			<Button class="mt-32 w-full" :disabled="saving" @click="save">Speichern</Button>
		</template>
	</section>
</template>
