<script setup>
import { RouterView } from 'vue-router';
import Header from '@/components/layout/Header.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import Toast from '@/components/ui/Toast.vue';

/**
 * The shell ([[07-dashboard]]): the site's header with legacy's dashboard menu,
 * and the screen under it — inside the site's white 1100px column, which
 * `resources/views/dashboard.blade.php` draws.
 */
</script>

<template>
	<Header />
	<main class="pb-24 sm:pb-32">
		<RouterView />
	</main>
	<Toast />
	<ConfirmDialog />
</template>
