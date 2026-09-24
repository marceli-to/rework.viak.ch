<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { cropMedia, deleteMedia, fetchCourseMedia, orderCourseMedia, setMediaRole, updateMedia, uploadCourseMedia } from '@/api/media';
import { confirm } from '@/composables/useConfirm';
import { useSortable } from '@/composables/useSortable';
import { toast } from '@/composables/useToast';
import Button from '@/components/ui/Button.vue';
import DropBox from '@/components/form/DropBox.vue';
import Field from '@/components/form/Field.vue';
import Lightbox from '@/components/ui/Lightbox.vue';
import Select from '@/components/form/Select.vue';
import IconCrop from '@/components/icons/Crop.vue';
import IconEdit from '@/components/icons/Edit.vue';
import IconTrash from '@/components/icons/Trash.vue';

/**
 * *Bilder* on the course form — legacy's `shared/modules/images`, measured on
 * its dashboard on 2026-09-24, on the media subsystem ported from
 * `forrerzimmermann.ch` ([[07-dashboard]]).
 *
 * The drop box, then the images as cards, three to a row 24px apart: a 2px
 * grey frame with 8px inside, the image **in its crop**, its type in white on
 * grey over the top right corner, and 18px icons under it. Cards drag into
 * order, which is the order the course page shows its visuals in.
 *
 * **Every action saves at once**, as legacy's does — this section is not part
 * of the course form's save.
 *
 * **On a course not yet saved, the images wait in the browser** (Marcel,
 * 2026-09-24 — legacy said *Bilder können erst nach dem Speichern hochgeladen
 * werden*). They show as cards at once and take their type, alt text, caption
 * and order; the form's *Speichern* creates the course and then calls
 * `flush()`, which uploads them in that order and applies the rest. **Cropping
 * waits for the upload**: a large file is scaled down on the server, and a
 * crop drawn on the original's pixels would land in the wrong place.
 *
 * Left out, both on the numbers: legacy's eye icon (one of 333 images was ever
 * hidden) and its *Listen Ansicht*.
 */
const props = defineProps({ course: { type: String, default: null } });

const staging = computed(() => props.course === null);
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 16 * 1024 * 1024;

const ROLES = [
	{ value: 'teaser', label: 'Vorschau' },
	{ value: 'visual', label: 'Hauptbild' },
	{ value: 'og', label: 'OpenGraph' },
];
const roleLabel = (role) => ROLES.find((option) => option.value === role)?.label;

/** The crop's shape follows what the image is for: a square card, a 16:9 page. */
const ratioFor = (role) => (role === 'teaser' ? 1 : 16 / 9);

/** What a card shows is the crop, else the whole file. */
const shape = (image) => (image.crop ? `${image.crop.w} / ${image.crop.h}` : `${image.width || 16} / ${image.height || 9}`);

const images = ref([]);
const uploads = ref([]);
const loading = ref(true);

onMounted(async () => {
	if (staging.value) {
		loading.value = false;
		return;
	}

	try {
		images.value = await fetchCourseMedia(props.course);
	} finally {
		loading.value = false;
	}
});

let staged = 0;

/** Held in the browser until the course exists; checked here as the server would check them. */
function stage(files) {
	for (const file of files) {
		if (!ACCEPTED.includes(file.type)) {
			uploads.value.push({ name: file.name, progress: 0, error: 'Nur JPG, PNG oder WebP.' });
			continue;
		}
		if (file.size > MAX_BYTES) {
			uploads.value.push({ name: file.name, progress: 0, error: 'Höchstens 16 MB.' });
			continue;
		}

		const image = {
			uuid: `neu-${++staged}`,
			staged: true,
			file,
			name: file.name,
			src: URL.createObjectURL(file),
			crop: null,
			width: null,
			height: null,
			role: images.value.some((item) => item.role === 'teaser') ? 'visual' : 'teaser',
			alt: '',
			caption: '',
		};
		image.preview = image.src;
		images.value.push(image);

		// Its shape, once the browser has read it.
		const probe = new Image();
		probe.onload = () => {
			const item = images.value.find((entry) => entry.uuid === image.uuid);
			if (item) Object.assign(item, { width: probe.naturalWidth, height: probe.naturalHeight });
		};
		probe.src = image.src;
	}
}

/** One teaser and one OpenGraph image, kept so in the browser as the server keeps it. */
function applyRole(image, role) {
	if (role === 'teaser' || role === 'og') {
		for (const other of images.value) if (other !== image && other.role === role) other.role = 'visual';
	}
	image.role = role;
}

/**
 * Uploads what was staged, in order, onto the course just created, then gives
 * each its type, alt text and caption. Answers the names that did not make it.
 */
let flushing = false;

async function flush(course) {
	flushing = true;
	const failed = [];

	for (const image of images.value.filter((item) => item.staged)) {
		try {
			let media = await uploadCourseMedia(course, image.file);
			if (image.alt || image.caption) media = await updateMedia(media.uuid, { alt: image.alt, caption: image.caption });
			if (media.role !== image.role) await setMediaRole(media.uuid, image.role);
		} catch {
			failed.push(image.name);
		}
		URL.revokeObjectURL(image.src);
	}

	images.value = await fetchCourseMedia(course);
	flushing = false;

	return failed;
}

// *Kurs erfassen* becomes *Kurs bearbeiten* on the same component, so the
// section is told its course; it reads the course's images then — unless it is
// uploading them itself, which reads them when it is done.
watch(() => props.course, async (course) => {
	if (course && !flushing) images.value = await fetchCourseMedia(course);
});

const pending = computed(() => images.value.filter((item) => item.staged).length);

defineExpose({ flush, pending });

/** Swap in the server's answer; a role change can also move a flag off a sibling, so those reload. */
async function refresh(changed, siblings = false) {
	if (siblings) {
		images.value = await fetchCourseMedia(props.course);
		return;
	}
	const index = images.value.findIndex((image) => image.uuid === changed.uuid);
	if (index !== -1) images.value.splice(index, 1, changed);
}

// Uploads — one request per file, one after another, each with its bar.
async function upload(files) {
	if (staging.value) return stage(files);

	for (const file of files) {
		const entry = ref({ name: file.name, progress: 0, error: null });
		uploads.value.push(entry.value);

		try {
			const image = await uploadCourseMedia(props.course, file, (progress) => (entry.value.progress = progress));
			images.value.push(image);
			uploads.value.splice(uploads.value.indexOf(entry.value), 1);
		} catch (problem) {
			entry.value.error = problem.errors?.file?.[0] ?? problem.message;
		}
	}

	if (images.value.some((image) => image.role === 'teaser')) images.value = await fetchCourseMedia(props.course);
}

const { dragging, handlers } = useSortable(images, async (list) => {
	if (staging.value) return;

	try {
		await orderCourseMedia(props.course, list.map((image) => image.uuid));
		toast('Reihenfolge angepasst');
	} catch (problem) {
		toast(problem.message, 'error');
	}
});

// Edit: type, alt text, caption — legacy's lightbox.
const editing = ref(null);

function edit(image) {
	editing.value = { uuid: image.uuid, role: image.role, alt: image.alt, caption: image.caption, was: image.role };
}

async function saveEdit() {
	const { uuid, role, alt, caption, was } = editing.value;

	if (staging.value) {
		const image = images.value.find((item) => item.uuid === uuid);
		Object.assign(image, { alt, caption });
		applyRole(image, role);
		editing.value = null;
		return;
	}

	try {
		const updated = await updateMedia(uuid, { alt, caption });
		if (role !== was) {
			await setMediaRole(uuid, role);
			await refresh(updated, true);
		} else {
			await refresh(updated);
		}
		editing.value = null;
		toast('Gespeichert');
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

async function remove(image) {
	if (!(await confirm('Bitte Löschen bestätigen!', image.name))) return;

	if (image.staged) {
		URL.revokeObjectURL(image.src);
		images.value.splice(images.value.indexOf(image), 1);
		return;
	}

	try {
		await deleteMedia(image.uuid);
		images.value.splice(images.value.indexOf(image), 1);
		toast('Bild gelöscht');
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

// Crop — vue-advanced-cropper over the file itself, so the numbers are its pixels.
const cropping = ref(null);
const cropper = ref(null);

const cropDefaults = computed(() => {
	const crop = cropping.value?.crop;
	return crop ? { left: crop.x, top: crop.y, width: crop.w, height: crop.h } : undefined;
});

async function saveCrop() {
	const { coordinates } = cropper.value.getResult();

	try {
		await refresh(await cropMedia(cropping.value.uuid, {
			x: Math.round(coordinates.left),
			y: Math.round(coordinates.top),
			w: Math.round(coordinates.width),
			h: Math.round(coordinates.height),
		}));
		cropping.value = null;
		toast('Zuschnitt gespeichert');
	} catch (problem) {
		toast(problem.message, 'error');
	}
}

async function clearCrop() {
	await refresh(await cropMedia(cropping.value.uuid, { x: null, y: null, w: null, h: null }));
	cropping.value = null;
	toast('Zuschnitt entfernt');
}
</script>

<template>
	<div class="mt-16">
		<DropBox accept="image/jpeg,image/png,image/webp" restrictions="JPG, PNG, WebP | max. 16 MB" @files="upload" />

		<ul v-if="uploads.length" class="mt-16 sm:mt-24">
			<li v-for="entry in uploads" :key="entry.name" class="border-t border-black py-8 text-xs sm:text-md lg:text-lg">
				<div class="flex justify-between gap-16">
					<span class="min-w-0 truncate">{{ entry.name }}</span>
					<span :class="entry.error ? 'text-danger' : 'text-gray-400'">{{ entry.error ?? `${entry.progress} %` }}</span>
				</div>
				<div v-if="!entry.error" class="mt-4 h-2 bg-gray-200"><div class="h-full bg-teal transition-[width]" :style="{ width: `${entry.progress}%` }" /></div>
			</li>
		</ul>

		<p v-if="loading" class="mt-24">Wird geladen …</p>

		<div v-else-if="images.length" class="mt-24 grid grid-cols-2 items-start gap-16 sm:grid-cols-3 lg:gap-24">
			<article
				v-for="(image, index) in images"
				:key="image.uuid"
				draggable="true"
				class="cursor-grab border-2 border-gray-400 bg-white p-8"
				:class="{ 'opacity-40': dragging === index }"
				v-bind="handlers(index)"
			>
				<div class="relative">
					<!-- Its shape before it loads, from the crop or the file, so a
					     card never collapses; and not lazy — a course has a handful. -->
					<img :src="image.preview" :alt="image.alt" class="block w-full bg-gray-200" :style="{ aspectRatio: shape(image) }" draggable="false" />
					<span class="absolute top-8 right-8 bg-gray-400 px-12 py-8 text-md leading-[1.3] text-white">{{ roleLabel(image.role) }}</span>
				</div>
				<div class="mt-10 flex gap-12">
					<button type="button" title="Bearbeiten" class="size-18 hover:text-teal" @click="edit(image)"><IconEdit class="block" /></button>
					<button type="button" title="Löschen" class="size-18 hover:text-teal" @click="remove(image)"><IconTrash class="block" /></button>
					<button
						type="button"
						:title="image.staged ? 'Zuschneiden nach dem Speichern' : 'Zuschneiden'"
						:disabled="image.staged"
						class="size-18 hover:text-teal disabled:cursor-not-allowed disabled:text-gray-400"
						@click="cropping = image"
					>
						<IconCrop class="block" />
					</button>
				</div>
			</article>
		</div>

		<Lightbox v-if="editing" title="Bild bearbeiten" @close="editing = null">
			<form @submit.prevent="saveEdit">
				<Select v-model="editing.role" label="Typ" :options="ROLES" />
				<Field v-model="editing.alt" label="Bildbeschreibung" />
				<Field v-model="editing.caption" label="Bildlegende" />
				<Button type="submit" class="w-full">Speichern</Button>
			</form>
		</Lightbox>

		<Lightbox v-if="cropping" title="Bild zuschneiden" wide @close="cropping = null">
			<Cropper
				ref="cropper"
				:src="cropping.src"
				:stencil-props="{ aspectRatio: ratioFor(cropping.role) }"
				:default-position="cropDefaults ? { left: cropDefaults.left, top: cropDefaults.top } : undefined"
				:default-size="cropDefaults ? { width: cropDefaults.width, height: cropDefaults.height } : undefined"
				class="h-[60vh] bg-gray-200"
			/>
			<p class="mt-12 text-md text-gray-600 lg:text-lg">
				{{ cropping.role === 'teaser' ? 'Quadratisch — für die Kurskarte.' : '16:9 — für die Kursseite.' }}
			</p>
			<div class="mt-24 flex flex-col gap-12 sm:flex-row">
				<Button class="sm:flex-1" @click="saveCrop">Speichern</Button>
				<Button v-if="cropping.crop" variant="secondary" class="sm:flex-1" @click="clearCrop">Zuschnitt entfernen</Button>
			</div>
		</Lightbox>
	</div>
</template>
