<script setup>
import IconCross from '@/components/icons/Cross.vue';

/**
 * `resources/views/components/ui/lightbox.blade.php` — keep the classes in
 * step. A white veil at 90 %, a box with a 2px `#505050` border, 600 to 900px
 * wide from sm; a bold teal title with the close cross beside it, and 24px down
 * to the body. Escape or a click on the veil closes it.
 */
defineProps({
	title: { type: String, default: null },
	wide: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
</script>

<template>
	<Teleport to="body">
		<div
			class="fixed inset-0 z-[200] flex cursor-pointer items-center justify-center overflow-y-auto bg-white/90 text-lg leading-[1.3] sm:text-xl lg:text-3xl"
			role="dialog"
			aria-modal="true"
			:aria-label="title"
			@click.self="emit('close')"
			@keydown.esc="emit('close')"
		>
			<div class="max-w-[90%] cursor-default border-2 border-gray-600 bg-white p-12 sm:min-w-600 sm:p-24" :class="wide ? 'sm:w-900 sm:max-w-[90%]' : 'sm:max-w-900'">
				<div class="max-h-[90vh] overflow-y-auto px-4">
					<header v-if="title" class="flex justify-between">
						<h1 class="mb-12 font-bold text-teal">{{ title }}</h1>
						<button type="button" class="h-fit transition-colors hover:text-teal" aria-label="Schliessen" @click="emit('close')">
							<IconCross />
						</button>
					</header>
					<div :class="{ 'mt-24': title }"><slot /></div>
				</div>
			</div>
		</div>
	</Teleport>
</template>
