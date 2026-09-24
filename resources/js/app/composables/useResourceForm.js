import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { fetchForm } from '@/api/forms';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';

/**
 * Everything a dashboard form does besides drawing its fields — the part of
 * the field kit the course and testimonial forms had each written out by hand
 * ([[07-dashboard]], step 5).
 *
 * - **Loads** the schema and, when editing, the record; on create the schema's
 *   `defaults` are the record, so the form's shape is the same either way.
 * - **What it loads is what it saves**: the fields are the schema's defaults'
 *   keys; anything else on the record (`url`, `has_bookings`) is `meta`, read
 *   and never sent.
 * - **Saves**: *Speichern* goes back to the list, *Speichern und
 *   Weiterbearbeiten* stays — on a new record, by opening it for editing.
 * - **Guards unsaved changes** on the way out and on a reload.
 * - **Hooks** let a custom part of the form take part: the image section
 *   reports what it is holding and uploads it once a new course exists.
 */
export function useResourceForm({ schema: name, load, save, remove, list, edit, noun }) {
	const route = useRoute();
	const router = useRouter();

	const schema = ref(null);
	const form = ref(null);
	const meta = ref({});
	const id = ref(null);
	const errors = ref({});
	const saving = ref(false);
	const deleting = ref(false);
	const failed = ref(null);

	const creating = computed(() => !route.params.uuid);

	const hooks = [];
	provide('formHooks', { register: (hook) => hooks.push(hook) });

	let saved = '';
	const pending = () => hooks.reduce((sum, hook) => sum + (hook.pending?.() ?? 0), 0);
	const dirty = computed(() => form.value !== null && (JSON.stringify(form.value) !== saved || pending() > 0));

	function take(record) {
		const fields = {};
		const rest = {};
		for (const [key, value] of Object.entries(record)) {
			if (key in schema.value.defaults) fields[key] = value;
			else rest[key] = value;
		}
		id.value = record.uuid ?? null;
		meta.value = rest;
		form.value = fields;
		saved = JSON.stringify(fields);
	}

	onMounted(async () => {
		try {
			schema.value = await fetchForm(name);
			take(creating.value ? { ...schema.value.defaults } : await load(route.params.uuid));
		} catch (problem) {
			failed.value = problem.message;
		}
	});

	async function submit(stay = false) {
		saving.value = true;
		errors.value = {};
		const wasCreating = creating.value;

		try {
			take(await save(id.value, form.value));

			const missed = [];
			if (wasCreating) for (const hook of hooks) missed.push(...((await hook.afterCreate?.(id.value)) ?? []));

			const done = wasCreating ? `${noun} erfasst` : 'Gespeichert';
			toast(missed.length ? `${done}. Nicht hochgeladen: ${missed.join(', ')}` : done, missed.length ? 'error' : 'success');

			if (!stay) router.push(list);
			else if (wasCreating) router.replace(edit(id.value));
		} catch (problem) {
			errors.value = problem.errors ?? {};
			toast(Object.keys(errors.value).length ? 'Bitte die markierten Felder prüfen.' : problem.message, 'error');
		} finally {
			saving.value = false;
		}
	}

	async function destroy(question) {
		if (!(await confirm('Bitte Löschen bestätigen!', question))) return;

		deleting.value = true;
		try {
			await remove(id.value);
			saved = JSON.stringify(form.value);
			toast(`${noun} gelöscht`);
			router.push(list);
		} catch (problem) {
			toast(problem.message, 'error');
		} finally {
			deleting.value = false;
		}
	}

	onBeforeRouteLeave(async () => (dirty.value ? confirm('Änderungen verwerfen?', 'Was du hier geändert hast, ist noch nicht gespeichert.') : true));

	const warn = (event) => {
		if (dirty.value) event.preventDefault();
	};
	window.addEventListener('beforeunload', warn);
	onBeforeUnmount(() => window.removeEventListener('beforeunload', warn));

	return { schema, form, meta, id, errors, saving, deleting, failed, creating, submit, destroy };
}
