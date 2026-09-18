<script setup>
defineProps({
	size: { type: String, default: 'lg' },
});
</script>

<template>
	<div>
		<svg aria-hidden="true" class="w-20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" v-if="$props.size == 'lg'">
			<path fill="currentColor" d="M20 9h-9V0H9v9H0v2h9v9h2v-9h9z"/>
		</svg>
		<svg aria-hidden="true" class="w-12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" v-else-if="$props.size == 'tiny'">
			<path d="M12 5.4H6.6V0H5.4V5.4H0V6.6H5.4V12H6.6V6.6H12V5.4Z" fill="currentColor"/>
		</svg>
		<svg aria-hidden="true" class="w-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" v-else>
			<path fill="currentColor" d="M16 7.2H8.8V0H7.2v7.2H0v1.6h7.2V16h1.6V8.8H16V7.2z"/>
		</svg>
	</div>
</template>
