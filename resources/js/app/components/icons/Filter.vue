<script setup>
defineProps({
	active: { type: Boolean, default: false },
});
</script>

<template>
	<svg aria-hidden="true" class="w-22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22">
		<g v-if="!$props.active">
			<path fill="currentColor" d="M0 0l8.556 9.778V22h4.889V9.777L22.001 0z"/>
		</g>
		<g v-else>
			<path fill="currentColor" d="M20.116 0L11 9.01 1.886 0 0 1.886 9.166 11l-8.59 8.538L2.41 21.53 11 12.939l8.54 8.591 1.886-1.992L12.834 11 22 1.886z"/>
		</g>
	</svg>
</template>
