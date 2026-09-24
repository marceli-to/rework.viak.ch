import { createRouter, createWebHistory } from 'vue-router';

const Pending = () => import('@/views/Pending.vue');

/**
 * The dashboard's screens ([[07-dashboard]]). German paths, as the site's are.
 *
 * Everything legacy's menu offers is routed from the start; what is not built
 * yet renders `Pending` under its own title, and each one is replaced by its
 * view as the build order reaches it.
 */
const pending = (path, name, title) => ({ path: `/dashboard/${path}`, name, component: Pending, meta: { title } });

const routes = [
	{ path: '/dashboard', redirect: { name: 'courses' } },
	{
		path: '/dashboard/kurse',
		name: 'courses',
		component: () => import('@/views/Course/Index.vue'),
		meta: { title: 'Kurse' },
	},

	{
		path: '/dashboard/kurs/erfassen',
		name: 'course.create',
		component: () => import('@/views/Course/Form.vue'),
		meta: { title: 'Kurs erfassen' },
	},
	{
		path: '/dashboard/kurs/:uuid',
		name: 'course.edit',
		component: () => import('@/views/Course/Form.vue'),
		meta: { title: 'Kurs bearbeiten' },
	},
	pending('kurs/:uuid/kursdaten', 'course.events', 'Kursdaten'),
	pending('kurs/:uuid/kursdatum/erfassen', 'event.create', 'Kursdatum erfassen'),
	pending('kursdatum/:uuid', 'event.show', 'Kursdatum'),
	pending('kursdatum/:uuid/bearbeiten', 'event.edit', 'Kursdatum bearbeiten'),

	pending('experten', 'experts', 'Experten'),
	pending('studenten', 'students', 'Studenten'),
	pending('rechnungen', 'backoffice.invoices', 'Rechnungen'),
	pending('exporte', 'backoffice.exports', 'Exporte'),
	pending('rabatt-codes', 'discount-codes', 'Rabatt-Codes'),
	{
		path: '/dashboard/testimonials',
		name: 'content.testimonials',
		component: () => import('@/views/Testimonial/Index.vue'),
		meta: { title: 'Testimonials' },
	},
	{
		path: '/dashboard/testimonial/erfassen',
		name: 'content.testimonial.create',
		component: () => import('@/views/Testimonial/Form.vue'),
		meta: { title: 'Testimonial erfassen' },
	},
	{
		path: '/dashboard/testimonial/:uuid',
		name: 'content.testimonial.edit',
		component: () => import('@/views/Testimonial/Form.vue'),
		meta: { title: 'Testimonial bearbeiten' },
	},
	pending('news', 'content.news', 'News'),
	pending('einstellungen', 'settings', 'Einstellungen'),
	pending('profil', 'profile', 'Mein Profil'),

	// The old list's address, from before the two modes were one screen.
	{ path: '/dashboard/termine', redirect: { name: 'courses' } },
];

const router = createRouter({
	history: createWebHistory(),
	routes,
	scrollBehavior: () => ({ top: 0 }),
});

router.afterEach((to) => {
	document.title = `${to.meta.title ?? 'Dashboard'} • Visualisierungs-Akademie`;
});

export default router;
