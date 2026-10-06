<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchPageTestimonials, savePageTestimonials } from '@/api/pages';
import { toast } from '@/composables/useToast';
import Button from '@/components/ui/Button.vue';
import ListHeader from '@/components/list/ListHeader.vue';
import Loading from '@/components/ui/Loading.vue';
import TestimonialPicker from '@/components/testimonial/TestimonialPicker.vue';

/**
 * *Seiteninhalte → Firmenschulung* ([[07-dashboard]]): the testimonials the
 * page shows under *Kundenmeinungen*, picked and ordered ([[Page]]). The copy
 * is the page's own, in Blade, so this is all there is to edit.
 */
const route = useRoute();
const key = computed(() => route.params.page);

const label = ref('');
const testimonials = ref([]);
const picked = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);

onMounted(async () => {
	try {
		const data = await fetchPageTestimonials(key.value);
		label.value = data.label;
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
		await savePageTestimonials(key.value, picked.value);
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
		<ListHeader :title="label || 'Seite'" />

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
