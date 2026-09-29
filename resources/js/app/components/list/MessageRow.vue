<script setup>
import { computed, ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import IconCross from '@/components/icons/Cross.vue';
import Overlay from '@/components/ui/Overlay.vue';
import { shortDate } from '@/support/format';

/**
 * One note in an event's thread: `resources/views/components/row/message.blade.php`,
 * which is legacy's `shared/modules/messages/components/Item.vue`. Keep the two
 * in step (Marcel, 2026-09-29: match legacy).
 *
 * **A row and a box, not the message.** The row is the date, the sender, 35
 * characters of the body and a teal *Anzeigen*; the message opens in legacy's
 * box: *Datum* and *Absender* over a rule, the subject, the body, and the
 * *Anhänge* under a rule. 700 wide at most and 480 at least from sm, a 2px
 * `#505050` border, the cross 32px in from the window's corner. The veil,
 * Escape and a click beside the box are [[Overlay]]'s.
 */
const props = defineProps({ message: { type: Object, required: true } });

const open = ref(false);

// `Str::limit(strip_tags(…), 35)`, the portal's preview.
const preview = computed(() => {
	const text = new DOMParser().parseFromString(props.message.body, 'text/html').body.textContent.trim();
	return text.length > 35 ? `${text.slice(0, 35)}...` : text;
});
</script>

<template>
	<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<div class="mb-8 sm:hidden">{{ shortDate(message.created_at) }} – {{ message.author }}</div>
			<div class="mb-4 max-sm:hidden sm:col-span-2">{{ shortDate(message.created_at) }}</div>
			<div class="mb-4 max-sm:hidden sm:col-span-3">{{ message.author }}</div>
			<div class="mb-4 sm:col-span-4 lg:col-span-5">{{ preview }}</div>
			<div class="mt-24 sm:col-span-3 sm:mt-0 lg:col-span-2">
				<Button class="w-full" title="Nachricht anzeigen" @click="open = true">Anzeigen</Button>
			</div>
		</div>
	</article>

	<Overlay v-if="open" class="z-[200] leading-[1.3] sm:text-lg lg:text-xl" role="dialog" :aria-label="message.subject" @close="open = false">
		<button type="button" class="absolute top-16 right-16 z-[3001] block transition-colors hover:text-teal sm:top-32 sm:right-32" aria-label="Schliessen" @click="open = false">
			<IconCross />
		</button>

		<div class="max-h-full w-[90%] cursor-default overflow-y-auto border-2 border-gray-600 bg-white p-8 sm:w-auto sm:max-w-700 sm:min-w-480 sm:p-16">
			<header class="mb-12 border-b border-gray-600 pb-8 sm:mb-24">
				<div class="mb-4 grid grid-cols-12 pr-12">
					<div class="col-span-2 text-xs sm:text-md lg:text-lg">Datum</div>
					<div class="col-span-10 text-xs sm:text-md lg:text-lg">{{ shortDate(message.created_at) }}</div>
				</div>
				<div class="mb-4 grid grid-cols-12">
					<div class="col-span-2 text-xs sm:text-md lg:text-lg">Absender</div>
					<div class="col-span-10 text-xs sm:text-md lg:text-lg">{{ message.author }}</div>
				</div>
			</header>

			<p v-if="message.subject" class="mb-12 lg:mb-16">{{ message.subject }}</p>

			<!-- `x-ui.rich-text`'s classes; the body went through [[RichText]]'s allowlist on the server. -->
			<div
				class="[&_a]:underline [&_a]:decoration-1 [&_a]:underline-offset-[3px] [&_a:hover]:no-underline [&_b]:font-bold [&_em]:italic [&_i]:italic [&_li]:ml-20 [&_li]:list-item [&_li>p]:mb-0 [&_ol]:m-0 [&_ol]:list-decimal [&_ol]:p-0 [&_p]:mb-12 [&_p:last-child]:mb-0 [&_strong]:font-bold [&_ul]:m-0 [&_ul]:list-disc [&_ul]:p-0 lg:[&_p]:mb-16"
				v-html="message.body"
			/>

			<footer v-if="message.attachments.length" class="mt-12 border-t border-gray-600 pt-8 sm:mt-24">
				<div class="mb-4 text-xs sm:text-md lg:text-lg">Anhänge</div>
				<a v-for="file in message.attachments" :key="file.uuid" :href="file.url" class="block text-gray-600 underline transition-colors hover:text-gray-400 hover:no-underline">{{ file.name }}</a>
			</footer>
		</div>
	</Overlay>
</template>
