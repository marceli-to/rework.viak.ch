import { createRouter, createWebHistory } from 'vue-router';

/**
 * Dashboard SPA routes. The legacy app shipped three separate bundles
 * (dashboard / expert / student); they collapse into this one router with
 * role-gated routes — see .rework/00-foundation.md.
 */
const routes = [
	{
		path: '/dashboard',
		name: 'dashboard.home',
		component: () => import('@/views/Home.vue'),
	},
];

export default createRouter({
	history: createWebHistory(),
	routes,
});
