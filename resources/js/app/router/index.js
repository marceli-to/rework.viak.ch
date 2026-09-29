import { createRouter, createWebHistory } from 'vue-router';
import { done, start } from '@/composables/useProgress';

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
	{
		// `:course`, not `:uuid`: the form is creating when it has no `uuid`.
		path: '/dashboard/kurs/:course/kursdatum/erfassen',
		name: 'event.create',
		component: () => import('@/views/Event/Form.vue'),
		meta: { title: 'Kursdatum erfassen' },
	},
	pending('kursdatum/:uuid', 'event.show', 'Kursdatum'),
	{
		path: '/dashboard/kursdatum/:uuid/bearbeiten',
		name: 'event.edit',
		component: () => import('@/views/Event/Form.vue'),
		meta: { title: 'Kursdatum bearbeiten' },
	},

	{
		path: '/dashboard/experten',
		name: 'experts',
		component: () => import('@/views/Expert/Index.vue'),
		meta: { title: 'Experten' },
	},
	{
		path: '/dashboard/experte/erfassen',
		name: 'expert.create',
		component: () => import('@/views/Expert/Form.vue'),
		meta: { title: 'Experte hinzufügen' },
	},
	{
		path: '/dashboard/experte/:uuid',
		name: 'expert.edit',
		component: () => import('@/views/Expert/Form.vue'),
		meta: { title: 'Experte bearbeiten' },
	},
	{
		path: '/dashboard/studenten',
		name: 'students',
		component: () => import('@/views/Student/Index.vue'),
		meta: { title: 'Studenten' },
	},
	{
		path: '/dashboard/student/erfassen',
		name: 'student.create',
		component: () => import('@/views/Student/Form.vue'),
		meta: { title: 'Student hinzufügen' },
	},
	{
		path: '/dashboard/student/:uuid/bearbeiten',
		name: 'student.edit',
		component: () => import('@/views/Student/Form.vue'),
		meta: { title: 'Student bearbeiten' },
	},
	// Bookings, documents, cancelling: an operational screen, step 7.
	pending('student/:uuid', 'student.show', 'Student'),
	{
		path: '/dashboard/rechnungen',
		name: 'backoffice.invoices',
		component: () => import('@/views/Invoice/Index.vue'),
		meta: { title: 'Rechnungen' },
	},
	{
		path: '/dashboard/rechnung/:uuid',
		name: 'backoffice.invoice.edit',
		component: () => import('@/views/Invoice/Form.vue'),
		meta: { title: 'Rechnung bearbeiten' },
	},
	{
		path: '/dashboard/exporte',
		name: 'backoffice.exports',
		component: () => import('@/views/Export/Index.vue'),
		meta: { title: 'Exporte' },
	},
	{
		path: '/dashboard/rabatt-codes',
		name: 'discount-codes',
		component: () => import('@/views/DiscountCode/Index.vue'),
		meta: { title: 'Rabatt-Codes' },
	},
	{
		path: '/dashboard/rabatt-code/erfassen',
		name: 'discount-code.create',
		component: () => import('@/views/DiscountCode/Form.vue'),
		meta: { title: 'Rabatt-Code erfassen' },
	},
	{
		path: '/dashboard/rabatt-code/:uuid',
		name: 'discount-code.edit',
		component: () => import('@/views/DiscountCode/Form.vue'),
		meta: { title: 'Rabatt-Code bearbeiten' },
	},
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
	{
		path: '/dashboard/einstellungen',
		name: 'settings',
		component: () => import('@/views/Setting/Index.vue'),
		meta: { title: 'Einstellungen' },
	},
	{
		path: '/dashboard/einstellungen/:kind/erfassen',
		name: 'setting.create',
		component: () => import('@/views/Setting/Form.vue'),
		meta: { title: 'Einstellungen' },
	},
	{
		path: '/dashboard/einstellungen/:kind/:uuid',
		name: 'setting.edit',
		component: () => import('@/views/Setting/Form.vue'),
		meta: { title: 'Einstellungen' },
	},
	{
		path: '/dashboard/profil',
		name: 'profile',
		component: () => import('@/views/Profile/Form.vue'),
		meta: { title: 'Mein Profil' },
	},

	// The old list's address, from before the two modes were one screen.
	{ path: '/dashboard/termine', redirect: { name: 'courses' } },
];

const router = createRouter({
	history: createWebHistory(),
	routes,
	scrollBehavior: () => ({ top: 0 }),
});

/*
 * The bar across the top while a screen's code is on its way. A flag, not a
 * count: a navigation a guard redirects starts again without ending first.
 */
let navigating = false;
const arrived = () => {
	if (navigating) done();
	navigating = false;
};

router.beforeEach(() => {
	if (!navigating) start();
	navigating = true;
});
router.onError(arrived);

/*
 * Each screen's last query, so a form's way back lands on the list as it was
 * left: a search, a mode. Without it, *Zurück* and *Speichern* dropped them.
 */
const lastQuery = {};

/** Where a form goes back to: `list`, with the query that list last had. */
export const returnTo = (list) => (lastQuery[list.name] ? { name: list.name, query: lastQuery[list.name] } : list);

router.afterEach((to) => {
	arrived();
	if (to.name) lastQuery[to.name] = to.query;
	document.title = `${to.meta.title ?? 'Dashboard'} • Visualisierungs-Akademie`;
});

export default router;
