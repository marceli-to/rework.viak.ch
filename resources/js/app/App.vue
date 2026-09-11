<script setup>
import { RouterLink, RouterView } from 'vue-router';

const nav = [
	{ name: 'events.index', label: 'Kursdaten' },
	{ name: 'courses.index', label: 'Kurse' },
];
</script>

<template>
	<div class="min-h-screen bg-paper">
		<header class="border-b border-line bg-white">
			<div class="mx-auto flex max-w-[1280px] items-center gap-8 px-6 py-3">
				<span class="text-base font-semibold tracking-tight text-ink">VIAK</span>

				<nav class="flex gap-5 text-sm">
					<RouterLink
						v-for="item in nav"
						:key="item.name"
						:to="{ name: item.name }"
						class="text-muted transition hover:text-teal-dark"
						active-class="font-semibold text-teal-dark"
					>
						{{ item.label }}
					</RouterLink>
				</nav>

				<a href="/" class="ml-auto text-xs text-faint hover:text-teal-dark">Zur Website &rarr;</a>
			</div>
		</header>

		<main class="mx-auto max-w-[1280px] px-6 py-8">
			<RouterView />
		</main>
	</div>
</template>
