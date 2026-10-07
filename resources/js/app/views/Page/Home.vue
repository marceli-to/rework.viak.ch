<script setup>
import { onMounted, ref } from 'vue';
import { fetchPageContent, fetchPageTestimonials, savePageContent, savePageTestimonials } from '@/api/pages';
import { toast } from '@/composables/useToast';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Editor from '@/components/form/Editor.vue';
import Field from '@/components/form/Field.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import ImageSection from '@/components/media/ImageSection.vue';
import TestimonialPicker from '@/components/testimonial/TestimonialPicker.vue';

/**
 * *Seiteninhalte → Startseite* ([[Page]]), in collapsibles as a course form's
 * sections are (Marcel, 2026-10-07):
 *
 * - **Slideshow Bilder**: the intro's slider, legacy's home hero, the course
 *   form's image section on `Page::for('home')`, dragged into the slider's
 *   order. The one marked *OpenGraph* is the page's `og:image` and stays out
 *   of the slider.
 * - **Über uns**: the About teaser's heading, text and image
 *   ([[HomeAboutSchema]], `Page::for('home-about')`).
 * - **Rezensionen**: the testimonials under *Kundenmeinungen* (marker 10).
 *
 * Images save at once, as everywhere; *Über uns* and *Rezensionen* each have
 * their own *Speichern* inside (Marcel, 2026-10-07). The rest of the
 * homepage's copy is in Blade.
 */
const about = ref({ title: '', text: '' });
const testimonials = ref([]);
const picked = ref([]);
const errors = ref({});
const loading = ref(true);
const saving = ref(null);
const error = ref(null);

onMounted(async () => {
	try {
		const [content, data] = await Promise.all([fetchPageContent('home-about'), fetchPageTestimonials('home')]);
		about.value = { title: content.title, text: content.text };
		testimonials.value = data.testimonials;
		picked.value = data.picked;
	} catch (problem) {
		error.value = problem.message;
	} finally {
		loading.value = false;
	}
});

/** One part at a time: `which` is the collapsible whose button was pressed. */
async function save(which) {
	saving.value = which;
	if (which === 'about') errors.value = {};
	try {
		if (which === 'about') await savePageContent('home-about', about.value);
		else await savePageTestimonials('home', picked.value);
		toast('Gespeichert');
	} catch (problem) {
		if (which === 'about') errors.value = problem.errors ?? {};
		toast(Object.keys(problem.errors ?? {}).length ? 'Bitte die markierten Felder prüfen.' : problem.message, 'error');
	} finally {
		saving.value = null;
	}
}
</script>

<template>
	<section>
		<ListHeader title="Startseite" />

		<p v-if="error" class="mt-32 text-danger">{{ error }}</p>
		<Loading v-else-if="loading" class="mt-32" />

		<div v-else class="mt-32">
			<Collapsible>
				<template #title>Slideshow Bilder</template>
				<div class="mt-16">
					<ImageSection record="home" owner="pages" />
				</div>
			</Collapsible>

			<Collapsible :invalid="Boolean(errors.title || errors.text)">
				<template #title>Über uns</template>
				<div class="mt-16">
					<Field v-model="about.title" label="Titel" required :error="errors.title?.[0]" />
					<Editor v-model="about.text" label="Text" required :error="errors.text?.[0]" />
					<h3 class="mb-16 text-md sm:text-lg lg:text-xl">Bild</h3>
					<ImageSection record="home-about" owner="pages" />
					<Button class="mt-32 w-full" :disabled="saving === 'about'" @click="save('about')">{{ saving === 'about' ? 'Wird gespeichert …' : 'Speichern' }}</Button>
				</div>
			</Collapsible>

			<Collapsible :count="picked.length">
				<template #title>Rezensionen</template>
				<TestimonialPicker v-model="picked" :testimonials="testimonials" class="mt-16" />
				<Button class="mt-32 w-full" :disabled="saving === 'testimonials'" @click="save('testimonials')">{{ saving === 'testimonials' ? 'Wird gespeichert …' : 'Speichern' }}</Button>
			</Collapsible>
		</div>
	</section>
</template>
