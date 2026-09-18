<script setup>
import { RouterLink, RouterView } from 'vue-router';

const nav = [
	{ name: 'events.index', label: 'Kursdaten' },
	{ name: 'courses.index', label: 'Kurse' },
];
</script>

<template>
	<div class="min-h-screen bg-gray-200">
		<header class="border-b border-gray-400 bg-white">
			<div class="mx-auto flex max-w-[1280px] items-center gap-32 px-24 py-12">
				<span class="text-lg font-semibold tracking-tight text-black">VIAK</span>

				<nav class="flex gap-20 text-md">
					<RouterLink
						v-for="item in nav"
						:key="item.name"
						:to="{ name: item.name }"
						class="text-gray-600 transition hover:text-teal"
						active-class="font-semibold text-teal"
					>
						{{ item.label }}
					</RouterLink>
				</nav>

				<a href="/" class="ml-auto text-xs text-gray-400 hover:text-teal">Zur Website &rarr;</a>
			</div>
		</header>

		<main class="mx-auto max-w-[1280px] px-24 py-32">
			<RouterView />
		</main>
	</div>
</template>
