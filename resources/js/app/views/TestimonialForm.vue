<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { deleteTestimonial, fetchTestimonial, saveTestimonial } from '@/api/testimonials';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import Field from '@/components/form/Field.vue';
import Textarea from '@/components/form/Textarea.vue';

/**
 * *Testimonial erfassen* / *bearbeiten* ([[07-dashboard]]) — the second form
 * the field kit is extracted from, built **by hand** like the course form so
 * the kit is whatever the two turn out to share.
 *
 * What the mockups show of a testimonial: the quote, a name, one line of
 * context. *Auf der Startseite* is the one placement decided so far (homepage
 * marker 10); the others arrive with their pages.
 */
const route = useRoute();
const router = useRouter();

const uuid = computed(() => route.params.uuid ?? null);
const creating = computed(() => uuid.value === null);
const back = { name: 'content.testimonials' };

const form = ref(null);
const id = ref(null);
const errors = ref({});
const saving = ref(false);
const failed = ref(null);

let saved = '';
const dirty = computed(() => form.value !== null && JSON.stringify(form.value) !== saved);

function load(data) {
	const { uuid: key, ...fields } = data;
	id.value = key;
	form.value = fields;
	saved = JSON.stringify(fields);
}

onMounted(async () => {
	try {
		if (creating.value) load({ uuid: null, quote: '', name: '', context: '', featured: false, publish: false });
		else load(await fetchTestimonial(uuid.value));
	} catch (problem) {
		failed.value = problem.message;
	}
});

const error = (key) => errors.value[key]?.[0] ?? null;

/** *Speichern* goes back to the list; *Speichern und Weiterbearbeiten* stays. */
async function submit(stay = false) {
	saving.value = true;
	errors.value = {};
	const wasCreating = creating.value;

	try {
		load(await saveTestimonial(id.value, form.value));
		toast(wasCreating ? 'Testimonial erfasst' : 'Gespeichert');

		if (!stay) router.push(back);
		else if (wasCreating) router.replace({ name: 'content.testimonial.edit', params: { uuid: id.value } });
	} catch (problem) {
		errors.value = problem.errors ?? {};
		toast(Object.keys(errors.value).length ? 'Bitte die markierten Felder prüfen.' : problem.message, 'error');
	} finally {
		saving.value = false;
	}
}

async function destroy() {
	if (!(await confirm('Bitte Löschen bestätigen!', form.value.name))) return;

	try {
		await deleteTestimonial(id.value);
		saved = JSON.stringify(form.value);
		toast('Testimonial gelöscht');
		router.push(back);
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

onBeforeRouteLeave(async () => (dirty.value ? confirm('Änderungen verwerfen?', 'Was du hier geändert hast, ist noch nicht gespeichert.') : true));

const warn = (event) => {
	if (dirty.value) event.preventDefault();
};
window.addEventListener('beforeunload', warn);
onBeforeUnmount(() => window.removeEventListener('beforeunload', warn));
</script>

<template>
	<p v-if="failed" class="text-danger">{{ failed }}</p>
	<p v-else-if="!form">Wird geladen …</p>

	<form v-else novalidate @submit.prevent="submit()">
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal max-sm:hidden">{{ creating ? 'Testimonial erfassen' : 'Testimonial bearbeiten' }}</h1>
				<BackLink :to="back" />
			</template>

			<Textarea v-model="form.quote" label="Zitat" required :rows="3" :error="error('quote')" />
			<Field v-model="form.name" label="Name" required :error="error('name')" />
			<Field v-model="form.context" label="Firma, Ort" :error="error('context')" />

			<div class="mb-32 flex gap-x-64 border-b border-black pb-24 lg:gap-x-80">
				<Checkbox v-model="form.publish">Publizieren</Checkbox>
				<Checkbox v-model="form.featured">Auf der Startseite</Checkbox>
			</div>

			<Button type="submit" class="w-full" :disabled="saving">{{ saving ? 'Wird gespeichert …' : 'Speichern' }}</Button>
			<Button variant="secondary" class="mt-12 w-full" :disabled="saving" @click="submit(true)">Speichern und Weiterbearbeiten</Button>

			<div v-if="!creating" class="mt-24 border-2 border-danger p-8 text-md text-danger sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl">
				<h2 class="mb-8 font-bold sm:mb-16">Testimonial löschen</h2>
				<p class="mb-12 lg:mb-16">Mit dieser Aktion wird das Testimonial gelöscht.</p>
				<div class="mt-12 sm:mt-24">
					<Button variant="danger" class="w-full" @click="destroy">Löschen</Button>
				</div>
			</div>
		</ArticleText>
	</form>
</template>
