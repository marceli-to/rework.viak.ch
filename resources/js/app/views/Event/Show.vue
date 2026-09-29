<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { fetchEventPage, setParticipation } from "@/api/events";
import { toast } from "@/composables/useToast";
import { returnTo } from "@/router";
import ArticleText from "@/components/layout/ArticleText.vue";
import BackLink from "@/components/ui/BackLink.vue";
import Checkbox from "@/components/form/Checkbox.vue";
import Collapsible from "@/components/ui/Collapsible.vue";
import EventRow from "@/components/course/EventRow.vue";
import Loading from "@/components/ui/Loading.vue";

/**
 * A course date's own page — legacy's `course/event/Show.vue` ([[07-dashboard]],
 * step 7), **so far only what attendance needs** (Marcel, 2026-09-29): the
 * date under *Informationen*, and *Teilnehmer*, each with legacy's tick,
 * *Teilgenommen?*. Only a ticked seat gets the participation confirmation
 * when the date closes. Once closed the tick turns into *Ja* / *Nein*; a
 * called-off date shows neither.
 *
 * Still to come from legacy's page: *Nachrichten*, *Kurs-Dokumente*,
 * *Teilnehmer hinzufügen* and the participant list as PDF.
 */
const route = useRoute();
const page = ref(null);
const error = ref(null);

const state = computed(() => page.value?.event.state);
const closed = computed(() => state.value === "closed");
const cancelled = computed(() => state.value === "cancelled");

onMounted(async () => {
	try {
		page.value = await fetchEventPage(route.params.uuid);
	} catch (problem) {
		error.value = problem.message;
	}
});

async function tick(participant, value) {
	const was = participant.participated;
	participant.participated = value;
	try {
		await setParticipation(participant.uuid, value);
	} catch (problem) {
		participant.participated = was;
		toast(problem.message, "error");
	}
}
</script>

<template>
	<p v-if="error" class="text-danger">{{ error }}</p>
	<Loading v-else-if="!page" />

	<section v-else>
		<ArticleText>
			<template #aside>
				<h1 class="font-bold text-teal">
					{{ page.course.number }} {{ page.course.title }}
				</h1>
				<BackLink :to="returnTo({ name: 'courses' })" />
			</template>
		</ArticleText>

		<!-- The aside has no column beside it here, so the lists keep clear of *Zurück*. -->
		<div class="mt-32 sm:mt-48">
			<Collapsible expanded>
				<template #title>Informationen</template>
				<EventRow :event="page.event" />
			</Collapsible>

			<Collapsible expanded>
				<template #title>Teilnehmer</template>

				<template v-if="page.participants.length">
					<div
						v-if="!cancelled"
						class="mt-12 flex justify-end sm:mt-24"
					>
						Teilgenommen?
					</div>

					<!-- Legacy's stacked row, 2/2/2/3/1/2 of twelve. -->
					<article
						v-for="participant in page.participants"
						:key="participant.uuid"
						class="mt-16 grid grid-cols-12 gap-x-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:gap-x-40 lg:text-xl"
					>
						<div class="col-span-12 sm:col-span-2">
							{{ participant.name }}
						</div>
						<div class="col-span-6 sm:col-span-2">
							{{ participant.city }}
						</div>
						<div class="col-span-6 sm:col-span-2">
							{{ participant.company }}
						</div>
						<div class="col-span-12 min-w-0 truncate sm:col-span-3">
							<a
								:href="`mailto:${participant.email}`"
								class="hover:text-teal"
								>{{ participant.email }}</a
							>
						</div>
						<div class="col-span-6 sm:col-span-1">
							{{ participant.has_rental ? "Mietcomputer" : "" }}
						</div>
						<div class="col-span-6 flex justify-end sm:col-span-2">
							<template v-if="!cancelled">
								<strong v-if="closed">{{
									participant.participated ? "Ja" : "Nein"
								}}</strong>
								<Checkbox
									v-else
									:model-value="participant.participated"
									@update:model-value="
										(value) => tick(participant, value)
									"
								>
									<span class="sr-only"
										>{{ participant.name }} hat
										teilgenommen</span
									>
								</Checkbox>
							</template>
						</div>
					</article>
				</template>
				<p v-else class="mt-16 sm:mt-32">
					Es sind keine Anmeldungen für diesen Kurs vorhanden.
				</p>
			</Collapsible>
		</div>
	</section>
</template>
