<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { deleteCourse, fetchCourse, fetchCourseOptions, saveCourse } from '@/api/courses';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import CheckboxGroup from '@/components/form/CheckboxGroup.vue';
import Editor from '@/components/form/Editor.vue';
import Field from '@/components/form/Field.vue';
import ImageSection from '@/components/media/ImageSection.vue';
import Textarea from '@/components/form/Textarea.vue';

/**
 * *Kurs erfassen* / *Kurs bearbeiten* — legacy's `views/course/Form.vue`,
 * measured on its dashboard on 2026-09-24 ([[07-dashboard]]).
 *
 * Built **by hand**: it is the first of the two forms the field kit is to be
 * extracted from (`04-content.md`, *Build it from two forms*), so it is plain
 * components on a plain object, and the repetition is the point — the kit is
 * whatever these two turn out to share.
 *
 * Legacy's layout, top to bottom: title and *Zurück* in the aside; the texts;
 * then *Facts*, *Einstellungen* (open), *Bilder*, *Videos* and *Metatags + SEO*
 * as collapsibles; *Speichern* full width; *Kurs löschen* in a red box.
 * What differs, and why:
 *
 * - **no DE / EN switch** — the admin edits German only, and the English stays
 *   as the port left it (`04-content.md`);
 * - **the number is prefilled with the next free one** on a new course, and
 *   refused if any course, deleted ones included, already has it — numbers
 *   are on invoices and are never reused;
 * - **no *Rezensionen* box** — it held the Elfsight embeds, which are being
 *   replaced by testimonials (`Open-Questions.md` #18);
 * - **the videos save with the form**, where legacy saved each on its own
 *   button;
 * - **deleting is refused while a date has bookings** — legacy deleted the
 *   course and every date with it;
 * - **images save on their own**, each action at once, as legacy's do
 *   ([[ImageSection]]) — so they are not part of what this form sends. On a
 *   new course they wait in the browser and go up right after *Speichern*,
 *   where legacy said they could only come after it.
 */
const route = useRoute();
const router = useRouter();

const uuid = computed(() => route.params.uuid ?? null);
const creating = computed(() => uuid.value === null);

const empty = () => ({
	number: '', title: '', subtitle: '', fee: '', online: false, publish: false,
	short_description: '', full_description: '', information_booking: '', information_content: '', summary: '',
	facts: ['', '', ''],
	categories: [], languages: [], levels: [], software: [], tags: [],
	videos: [],
	seo_description: '', seo_tags: '',
});

const form = ref(null);
const meta = ref({});
const options = ref(null);
const errors = ref({});
const saving = ref(false);
const failed = ref(null);

let saved = '';
const imageSection = ref(null);
// Images waiting to be uploaded with a new course are unsaved changes too.
const dirty = computed(() => form.value !== null && (JSON.stringify(form.value) !== saved || imageSection.value?.pending > 0));

function load(data) {
	const { uuid: id, url, has_bookings, ...fields } = data;
	meta.value = { uuid: id, url, has_bookings };
	form.value = fields;
	saved = JSON.stringify(fields);
}

onMounted(async () => {
	try {
		const [loaded, choices] = await Promise.all([creating.value ? null : fetchCourse(uuid.value), fetchCourseOptions()]);
		options.value = choices;
		if (loaded) load(loaded);
		else {
			form.value = { ...empty(), number: choices.next_number };
			saved = JSON.stringify(form.value);
			meta.value = {};
		}
	} catch (problem) {
		failed.value = problem.message;
	}
});

/** The first message for a field, and whether any field under a prefix failed. */
const error = (key) => errors.value[key]?.[0] ?? null;
const failedUnder = (...prefixes) => Object.keys(errors.value).some((key) => prefixes.some((prefix) => key === prefix || key.startsWith(`${prefix}.`)));

/**
 * *Speichern* saves and goes back to the list; *Speichern und
 * Weiterbearbeiten* saves and stays — on a new course, by opening it for
 * editing (Marcel, 2026-09-24; legacy's pair was *Speichern und schliessen* and
 * *Speichern*, on the create screen only).
 */
async function submit(stay = false) {
	saving.value = true;
	errors.value = {};

	const wasCreating = creating.value;

	try {
		const data = await saveCourse(meta.value.uuid, form.value);
		load(data);

		// A new course exists now, so the images held for it go up.
		const failed = wasCreating ? ((await imageSection.value?.flush(data.uuid)) ?? []) : [];
		const done = wasCreating ? 'Kurs erfasst' : 'Gespeichert';
		toast(failed.length ? `${done} — nicht hochgeladen: ${failed.join(', ')}` : done, failed.length ? 'error' : 'success');

		if (!stay) {
			router.push({ name: 'courses', query: { modus: 'kurse' } });
		} else if (wasCreating) {
			router.replace({ name: 'course.edit', params: { uuid: data.uuid } });
		}
	} catch (problem) {
		errors.value = problem.errors ?? {};
		toast(Object.keys(errors.value).length ? 'Bitte die markierten Felder prüfen.' : problem.message, 'error');
	} finally {
		saving.value = false;
	}
}

async function destroy() {
	if (!(await confirm('Bitte Löschen bestätigen!', 'Der Kurs wird mit allen Kursdaten gelöscht.'))) return;

	try {
		await deleteCourse(meta.value.uuid);
		saved = JSON.stringify(form.value);
		toast('Kurs gelöscht');
		router.push({ name: 'courses', query: { modus: 'kurse' } });
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

function addVideo() {
	form.value.videos.push({ uuid: null, title: '', code: '', publish: true });
}

// Unsaved changes: asked on the way out of the screen, and the browser asks
// on a reload or a closed tab.
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
				<h1 class="font-bold text-teal max-sm:hidden">{{ creating ? 'Kurs erfassen' : 'Kurs bearbeiten' }}</h1>
				<BackLink :to="{ name: 'courses', query: { modus: 'kurse' } }" />
			</template>

			<Field v-model="form.number" label="Nummer" type="number" required :error="error('number')" />
			<Field v-model="form.title" label="Titel" required :error="error('title')" />
			<Textarea v-model="form.subtitle" label="Subtitel" required :error="error('subtitle')" />
			<Field v-model="form.fee" label="Kosten" type="number" required :error="error('fee')" />
			<Editor v-model="form.short_description" label="Kurzbeschrieb" required :error="error('short_description')" />
			<Editor v-model="form.full_description" label="Detailbeschrieb" :error="error('full_description')" />
			<Editor v-model="form.information_booking" label="Weitere Informationen" :error="error('information_booking')" />
			<Editor v-model="form.information_content" :error="error('information_content')" />
			<Editor v-model="form.summary" label="Kursbeschreibung (PDF)" :error="error('summary')" />

			<Collapsible :invalid="failedUnder('facts')">
				<template #title>Facts</template>
				<div class="mt-16">
					<Editor v-for="(fact, index) in form.facts" :key="index" v-model="form.facts[index]" :error="error(`facts.${index}`)" />
				</div>
			</Collapsible>

			<Collapsible expanded :invalid="failedUnder('categories', 'languages', 'levels', 'software', 'tags')">
				<template #title>Einstellungen</template>
				<div class="mt-16 mb-32 flex gap-x-64 border-b border-black pb-24 lg:gap-x-80">
					<Checkbox v-model="form.online">Onlinekurs</Checkbox>
					<Checkbox v-model="form.publish">Publizieren</Checkbox>
				</div>
				<CheckboxGroup v-model="form.categories" label="Kategorien" required :options="options.categories" :error="error('categories')" />
				<CheckboxGroup v-model="form.languages" label="Sprachen" required :options="options.languages" :error="error('languages')" />
				<CheckboxGroup v-model="form.levels" label="Levels" required :options="options.levels" :error="error('levels')" />
				<CheckboxGroup v-model="form.software" label="Software" :options="options.software" />
				<CheckboxGroup v-model="form.tags" label="Tags" :options="options.tags" columns />
			</Collapsible>

			<Collapsible>
				<template #title>Bilder</template>
				<ImageSection ref="imageSection" :course="meta.uuid ?? null" />
			</Collapsible>

			<Collapsible :invalid="failedUnder('videos')">
				<template #title>Videos</template>
				<div class="mt-16">
					<div v-for="(video, index) in form.videos" :key="video.uuid ?? `neu-${index}`" class="mb-32 border-b border-black pb-24">
						<Field v-model="video.title" label="Titel" :error="error(`videos.${index}.title`)" />
						<Textarea v-model="video.code" label="Code" required mono :rows="3" :error="error(`videos.${index}.code`)" />
						<div class="flex items-center justify-between">
							<Checkbox v-model="video.publish">Publizieren</Checkbox>
							<button type="button" class="transition-colors hover:text-teal sm:text-lg lg:text-xl" @click="form.videos.splice(index, 1)">Entfernen</button>
						</div>
					</div>
					<!-- Legacy's *Video hinzufügen*: a dashed box across the column. -->
					<button type="button" class="block w-full border border-dashed border-black py-24 text-center transition-colors hover:border-teal hover:text-teal sm:text-lg lg:text-xl" @click="addVideo">
						Video hinzufügen
					</button>
				</div>
			</Collapsible>

			<Collapsible :invalid="failedUnder('seo_description', 'seo_tags')">
				<template #title>Metatags + SEO</template>
				<div class="mt-16">
					<Textarea v-model="form.seo_description" label="SEO - Beschreibung" :error="error('seo_description')" />
					<Textarea v-model="form.seo_tags" label="SEO - Keywords" :error="error('seo_tags')" />
				</div>
			</Collapsible>

			<Button type="submit" class="w-full" :disabled="saving">{{ saving ? 'Wird gespeichert …' : 'Speichern' }}</Button>
			<Button variant="secondary" class="mt-12 w-full" :disabled="saving" @click="submit(true)">Speichern und Weiterbearbeiten</Button>

			<!-- `.form-danger-zone.is-danger`, as the student's address form has it. -->
			<div v-if="!creating" class="mt-24 border-2 border-danger p-8 text-md text-danger sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl">
				<h2 class="mb-8 font-bold sm:mb-16">Kurs löschen</h2>
				<template v-if="meta.has_bookings">
					<p>Dieser Kurs hat Buchungen und kann nicht gelöscht werden. Setze ihn stattdessen auf nicht publiziert.</p>
				</template>
				<template v-else>
					<p class="mb-12 lg:mb-16">Mit dieser Aktion wird der Kurs inklusive aller Kursdaten gelöscht.</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="danger" class="w-full" @click="destroy">Löschen</Button>
					</div>
				</template>
			</div>
		</ArticleText>
	</form>
</template>
