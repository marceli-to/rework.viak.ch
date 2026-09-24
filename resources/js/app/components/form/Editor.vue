<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, shallowRef, useId, watch } from 'vue';
import IconEditorBold from '@/components/icons/EditorBold.vue';
import IconEditorBulletList from '@/components/icons/EditorBulletList.vue';
import IconEditorLink from '@/components/icons/EditorLink.vue';

/**
 * `resources/views/components/form/editor.blade.php` with
 * `resources/js/site/components/editor.js` — the message composer's tiptap
 * editor, in Vue. Keep the classes and the link rules in step.
 *
 * It replaces legacy's TinyMCE on the course form. Legacy's bar has seven
 * buttons; the ported course copy uses **bold, a bullet list and links and
 * nothing else** (counted 2026-09-24), which is exactly the composer's three,
 * and the schema is the same one (`js/shared/editor.js`) so whatever is typed
 * here is what [[EditorHtml]] keeps.
 *
 * A 1px black box, 320px high, the toolbar a 39px strip over the text. The
 * link bar opens over the text rather than pushing it down. `v-model` is the
 * HTML, and an empty editor is `''`, not `<p></p>`.
 *
 * **The Editor is held in a `shallowRef`**, never made reactive: ProseMirror
 * compares its own objects by identity, and a proxied editor fails in ways that
 * look like tiptap bugs — the same reason the Alpine one keeps it in a closure.
 */
const model = defineModel({ type: String, default: '' });

defineProps({
	label: { type: String, default: null },
	required: { type: Boolean, default: false },
	error: { type: String, default: null },
});

const id = useId();
const editor = shallowRef(null);
const content = ref(null);
const urlField = ref(null);

const active = ref({ bold: false, bulletList: false, link: false });
const linking = ref(false);
const url = ref('');

onMounted(async () => {
	const [{ Editor }, { default: extensions }] = await Promise.all([import('@tiptap/core'), import('../../../shared/editor')]);

	editor.value = new Editor({
		element: content.value,
		extensions: extensions(),
		content: model.value,
		editorProps: {
			attributes: {
				class: 'min-h-full outline-hidden',
				'aria-labelledby': `${id}-label`,
				'aria-multiline': 'true',
				role: 'textbox',
			},
		},
		onUpdate: ({ editor: instance }) => {
			model.value = instance.isEmpty ? '' : instance.getHTML();
		},
		onTransaction: ({ editor: instance }) => {
			active.value = {
				bold: instance.isActive('bold'),
				bulletList: instance.isActive('bulletList'),
				link: instance.isActive('link'),
			};
		},
	});
});

// A value set from outside — the form loading — replaces the content; the
// editor's own updates come back equal and are left alone.
watch(model, (value) => {
	const instance = editor.value;
	if (!instance) return;
	const current = instance.isEmpty ? '' : instance.getHTML();
	if (value !== current) instance.commands.setContent(value || '', { emitUpdate: false });
});

onBeforeUnmount(() => editor.value?.destroy());

const run = (command) => editor.value?.chain().focus()[command]().run();

async function openLink() {
	url.value = editor.value?.getAttributes('link').href ?? '';
	linking.value = true;
	await nextTick();
	urlField.value?.focus();
}

/** Legacy's rule, as the composer applies it: an address becomes `mailto:`, a bare host gets `https://`. */
function normalise(value) {
	if (value === '' || /^(https?:|mailto:|\/)/i.test(value)) return value;
	if (/^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test(value)) return `mailto:${value}`;
	return `https://${value.replace(/^\/+/, '')}`;
}

function applyLink() {
	const href = normalise(url.value.trim());
	const instance = editor.value;

	if (href === '') return removeLink();

	instance.chain().focus().extendMarkRange('link').setLink({ href }).run();

	if (instance.state.selection.empty && !instance.isActive('link')) {
		instance.chain().focus().insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run();
	}

	linking.value = false;
}

function removeLink() {
	editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
	linking.value = false;
}

function cancelLink() {
	linking.value = false;
	editor.value?.commands.focus();
}

const buttons = [
	{ state: 'bold', title: 'Fett', icon: IconEditorBold, action: () => run('toggleBold') },
	{ state: 'bulletList', title: 'Aufzählung', icon: IconEditorBulletList, action: () => run('toggleBulletList') },
	{ state: 'link', title: 'Link einfügen/bearbeiten', icon: IconEditorLink, action: openLink },
];
</script>

<template>
	<div class="relative mb-16 lg:mb-32">
		<!-- 8px under the label, where a field has 4: the editor's box needs
		     the room (Marcel, 2026-09-24). Both editors, kept in step. -->
		<label v-if="label" :id="`${id}-label`" class="mb-8 block text-md sm:text-lg lg:text-xl" @click="content?.querySelector('[contenteditable]')?.focus()">
			{{ label }}<template v-if="required"> *</template>
		</label>

		<div class="relative flex h-320 flex-col border bg-white" :class="error ? 'border-danger' : 'border-black'">
			<div role="toolbar" aria-label="Formatierung" class="flex h-39 shrink-0 border-b border-black bg-white">
				<div v-for="(button, index) in buttons" :key="button.state" class="flex items-start px-4" :class="{ 'border-r border-black': index < buttons.length - 1 }">
					<button
						type="button"
						:title="button.title"
						:aria-label="button.title"
						:aria-pressed="active[button.state]"
						:data-active="active[button.state] ? '' : null"
						class="mt-2 mb-3 flex size-34 items-center justify-center text-black transition-colors outline-hidden hover:text-teal focus-visible:text-teal data-active:text-teal"
						@mousedown.prevent
						@click="button.action"
					>
						<component :is="button.icon" />
					</button>
				</div>
			</div>

			<div
				v-show="linking"
				class="absolute inset-x-0 top-39 z-10 flex flex-wrap items-center gap-x-16 gap-y-8 border-b border-black bg-white px-16 py-8 text-md sm:text-lg"
				@keydown.esc.prevent.stop="cancelLink"
			>
				<input
					ref="urlField"
					v-model="url"
					type="text"
					aria-label="Adresse"
					placeholder="www.beispiel.ch oder name@beispiel.ch"
					class="min-w-0 flex-1 border-b border-black bg-transparent py-4 text-teal outline-hidden placeholder:text-gray-400"
					@keydown.enter.prevent="applyLink"
				/>
				<button type="button" class="transition-colors hover:text-teal" @click="applyLink">Übernehmen</button>
				<button v-show="active.link" type="button" class="transition-colors hover:text-teal" @click="removeLink">Entfernen</button>
				<button type="button" class="transition-colors hover:text-teal" @click="cancelLink">Abbrechen</button>
			</div>

			<div
				ref="content"
				class="min-h-0 flex-1 cursor-text overflow-y-auto p-16 text-md text-black sm:text-lg lg:text-xl [&_.ProseMirror]:min-h-full [&_p]:mb-12 lg:[&_p]:mb-16 [&_p:last-child]:mb-0 [&_li>p]:mb-0 [&_strong]:font-bold [&_ul]:mb-12 [&_ul]:list-disc lg:[&_ul]:mb-16 [&_ul:last-child]:mb-0 [&_li]:ml-20 [&_a]:underline [&_a]:decoration-1 [&_a]:underline-offset-[3px]"
				@click.self="content?.querySelector('[contenteditable]')?.focus()"
			/>
		</div>

		<div v-if="error" class="pt-8 text-md text-danger lg:text-lg">{{ error }}</div>
	</div>
</template>
