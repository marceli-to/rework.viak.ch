import { createRouter, createWebHistory } from 'vue-router';

/**
 * Legacy shipped three separate bundles (dashboard / expert / student). They
 * collapse into this one router; role gating lands with [[11-auth]].
 */
const routes = [
	{ path: '/dashboard', redirect: { name: 'events.index' } },
	{
		path: '/dashboard/kurse',
		name: 'courses.index',
		component: () => import('@/views/Courses.vue'),
		meta: { title: 'Kurse' },
	},
	{
		path: '/dashboard/termine',
		name: 'events.index',
		component: () => import('@/views/Events.vue'),
		meta: { title: 'Kursdaten' },
	},
];

export default createRouter({
	history: createWebHistory(),
	routes,
});
