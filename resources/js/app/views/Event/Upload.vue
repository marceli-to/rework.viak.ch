<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchEventPage, uploadFiles } from '@/api/events';
import { toast } from '@/composables/useToast';
import { goBack } from '@/router';
import ArticleText from '@/components/layout/ArticleText.vue';
import BackLink from '@/components/ui/BackLink.vue';
import Button from '@/components/ui/Button.vue';
import DropBox from '@/components/form/DropBox.vue';
import IconCross from '@/components/icons/Cross.vue';
import Loading from '@/components/ui/Loading.vue';

/**
 * *Dokumente hochladen* — legacy's `event-file-create`, a screen of its own
 * reached by the plus under *Kurs-Dokumente* ([[07-dashboard]]). The expert
 * portal's `site/expert/upload.blade.php` in the dashboard: the drop box, its
 * limits, the chosen files listed, and a full-width *Speichern*. **Nothing is
 * uploaded until *Speichern***, where legacy uploaded on drop and left 11 of
 * its 44 files attached to nothing. No *Bezeichnung*, as on the portal.
 */
const route = useRoute();
const back = { name: 'event.show', params: { uuid: route.params.uuid } };

const page = ref(null);
const error = ref(null);
const files = ref([]);
const problem = ref(null);
const saving = ref(false);

onMounted(async () => {
	try {
		page.value = await fetchEventPage(route.params.uuid);
	} catch (failed) {
		error.value = failed.message;
	}
});

const size = (bytes) => (bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

async function save() {
	saving.value = true;
	problem.value = null;
	try {
		await uploadFiles(route.params.uuid, files.value);
		toast(files.value.length === 1 ? 'Das Dokument wurde hochgeladen.' : 'Die Dokumente wurden hochgeladen.');
		files.value = [];
		goBack(back);
	} catch (failed) {
		problem.value = Object.values(failed.errors ?? {})[0]?.[0] ?? failed.message;
	} finally {
		saving.value = false;
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<ArticleText v-else>
		<template #aside>
			<h1 class="font-bold text-teal">Dokumente hochladen</h1>
			<p class="mt-8">{{ page.course.number }} {{ page.course.title }}</p>
			<BackLink :to="back" />
		</template>

		<form @submit.prevent="save">
			<div class="pb-16 sm:pb-32">
				<DropBox :accept="page.uploads.accept" :restrictions="page.uploads.restrictions" @files="(picked) => files.push(...picked)" />
				<p v-if="problem" class="pt-8 text-md text-danger lg:text-lg">{{ problem }}</p>
				<ul v-if="files.length" class="mt-16 sm:mt-24">
					<li v-for="(file, index) in files" :key="`${file.name}-${file.size}-${file.lastModified}`" class="flex items-center justify-between gap-16 border-t border-black py-8 text-xs sm:text-md lg:text-lg">
						<span class="min-w-0 truncate">{{ file.name }}</span>
						<span class="flex shrink-0 items-center gap-16">
							<span class="text-gray-400">{{ size(file.size) }}</span>
							<button type="button" class="transition-colors hover:text-teal" :aria-label="`Entfernen: ${file.name}`" @click="files.splice(index, 1)"><IconCross size="sm" /></button>
						</span>
					</li>
				</ul>
			</div>

			<!-- The portal's rule above a full-width *Speichern*, dead until a file is chosen. -->
			<div class="border-t border-black pt-12 lg:pt-24">
				<Button type="submit" class="w-full" :class="{ 'pointer-events-none opacity-60': !files.length || saving }">{{ saving ? 'Wird hochgeladen …' : 'Speichern' }}</Button>
			</div>
		</form>
	</ArticleText>
</template>
