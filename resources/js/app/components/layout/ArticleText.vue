<script setup>
/**
 * `resources/views/components/layout/article.blade.php` — legacy's
 * `ArticleText`: a `span-4` aside that sticks from sm, and a `span-8` column.
 * The dashboard's forms are drawn in it, as legacy's are.
 */
</script>

<template>
	<article class="relative">
		<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
			<aside class="sm:col-span-4">
				<div class="sm:sticky sm:top-20"><slot name="aside" /></div>
			</aside>
			<div v-if="$slots.default" class="mt-24 sm:col-span-8 sm:mt-0"><slot /></div>
		</div>
	</article>
</template>
