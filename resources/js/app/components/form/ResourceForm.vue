<script setup>
import { useResourceForm } from '@/composables/useResourceForm';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
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
});

const { schema, form, meta, id, errors, saving, failed, creating, submit, destroy } = useResourceForm(props);
</script>

<template>
	<p v-if="failed" class="text-danger">{{ failed }}</p>
	<p v-else-if="!form">Wird geladen …</p>

	<form v-else novalidate @submit.prevent="submit()">
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal max-sm:hidden">{{ creating ? titles.create : titles.edit }}</h1>
				<BackLink :to="list" />
			</template>

			<FormNode v-for="(field, index) in schema.fields" :key="field.name ?? `${field.type}-${index}`" :field="field" :model="form" :errors="errors" :record="id" />

			<Button type="submit" class="w-full" :disabled="saving">{{ saving ? 'Wird gespeichert …' : 'Speichern' }}</Button>
			<Button variant="secondary" class="mt-12 w-full" :disabled="saving" @click="submit(true)">Speichern und Weiterbearbeiten</Button>

			<!-- `.form-danger-zone.is-danger`, as the student's address form has it. -->
			<div v-if="!creating" class="mt-24 border-2 border-danger p-8 text-md text-danger sm:mt-48 sm:p-12 sm:pt-8 sm:text-lg lg:p-16 lg:pt-12 lg:text-xl">
				<h2 class="mb-8 font-bold sm:mb-16">{{ deletion.title }}</h2>
				<p v-if="blocked(meta)">{{ blocked(meta) }}</p>
				<template v-else>
					<p class="mb-12 lg:mb-16">{{ deletion.text }}</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="danger" class="w-full" @click="destroy(deletion.question(form))">Löschen</Button>
					</div>
				</template>
			</div>
		</ArticleText>
	</form>
</template>
