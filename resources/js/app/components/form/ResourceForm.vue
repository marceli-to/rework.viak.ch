<script setup>
import { useResourceForm } from '@/composables/useResourceForm';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import Loading from '@/components/ui/Loading.vue';
import FormNode from './FormNode.vue';

/**
 * A dashboard form, whole — the field kit's frame ([[07-dashboard]], step 5).
 *
 * Legacy's form screen: the title and *Zurück* in the aside, the schema's
 * fields in the column, *Speichern* and *Speichern und Weiterbearbeiten*, and
 * the red box to delete. A form is now a schema on the server and a few lines
 * here — what to load and save, what it is called, and when deleting is
 * refused (`blocked`, read from the record's meta).
 */
const props = defineProps({
	schema: { type: String, required: true },
	load: { type: Function, required: true },
	save: { type: Function, required: true },
	remove: { type: Function, required: true },
	list: { type: Object, required: true },
	edit: { type: Function, required: true },
	noun: { type: String, required: true },
	titles: { type: Object, required: true },
	deletion: { type: Object, required: true },
	blocked: { type: Function, default: () => null },
	// *Speichern und Weiterbearbeiten* — for a form long enough to come back to.
	stay: { type: Boolean, default: true },
	// A line about the record that is not a field — where a testimonial is used.
	note: { type: Function, default: () => null },
	// A record past changing — a course date that has run. Legacy's
	// `form.is-disabled`: the fields and *Speichern* at 40%, deleting gone.
	locked: { type: Function, default: () => false },
});

const { schema, form, meta, id, errors, saving, deleting, failed, creating, submit, destroy } = useResourceForm(props);

// A title may read the record: *Veranstaltung für* and the course's.
const title = (which) => (typeof props.titles[which] === 'function' ? props.titles[which](meta.value) : props.titles[which]);
</script>

<template>
	<p v-if="failed" class="text-danger">{{ failed }}</p>
	<Loading v-else-if="!form" />

	<form v-else novalidate @submit.prevent="submit()">
		<ArticleText>
			<template #aside>
				<h1 class="font-bold whitespace-pre-line text-teal max-sm:hidden">{{ title(creating ? 'create' : 'edit') }}</h1>
				<BackLink :to="list" />
			</template>

			<fieldset class="min-w-0" :disabled="!creating && locked(meta)" :class="{ 'pointer-events-none opacity-40 select-none': !creating && locked(meta) }">
				<FormNode v-for="(field, index) in schema.fields" :key="field.name ?? `${field.type}-${index}`" :field="field" :model="form" :errors="errors" :record="id" />

				<p v-if="!creating && note(meta)" class="mb-32 text-md lg:text-lg">{{ note(meta) }}</p>

				<Button type="submit" class="w-full" :disabled="saving">{{ saving ? 'Wird gespeichert …' : 'Speichern' }}</Button>
				<Button v-if="stay" variant="secondary" class="mt-12 w-full" :disabled="saving" @click="submit(true)">Speichern und Weiterbearbeiten</Button>
			</fieldset>

			<!-- `.form-danger-zone.is-danger`, as the student's address form has it. -->
			<div v-if="!creating && !locked(meta)" class="mt-24 border-2 border-danger p-8 text-md text-danger sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl">
				<h2 class="mb-8 font-bold sm:mb-16">{{ deletion.title }}</h2>
				<p v-if="blocked(meta)">{{ blocked(meta) }}</p>
				<template v-else>
					<p class="mb-12 lg:mb-16">{{ deletion.text }}</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="danger" class="w-full" :disabled="deleting" @click="destroy(deletion.question(form, meta))">{{ deleting ? 'Wird gelöscht …' : 'Löschen' }}</Button>
					</div>
				</template>
			</div>
		</ArticleText>
	</form>
</template>
