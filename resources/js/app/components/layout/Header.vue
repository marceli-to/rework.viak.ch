<script setup>
import { ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import IconLogo from '@/components/icons/Logo.vue';
import IconProfile from '@/components/icons/Profile.vue';

/**
 * The dashboard's header — `resources/views/components/layout/header.blade.php`,
 * with legacy's dashboard menu in place of the site's (`backend/dashboard/
 * App.vue`, `menu/_site.scss` `nav.site-menu-dashboard`). Measured on the
 * local legacy dashboard on 2026-09-24.
 *
 * The frame is the site header's: 28px above, an 80px row, the black rule, 28px
 * below, logo in `span-4` and the menu in `span-8`. What differs is the menu:
 *
 * - **the three sections sit left, 48px apart** (`margin-right: 12x`), where
 *   the site spreads its three across the `span-6`;
 * - **the profile icon and a burger sit right, 24px apart**, regular weight —
 *   the profile icon teal, as legacy marks it `is-active`;
 * - the burger opens a **240px white panel with a black rule on its left**,
 *   pinned to the right edge of the column and the full height of the screen:
 *   a close cross, then every other section right-aligned, bold, 42px apart.
 *
 * `sections` keep legacy's labels and order, with the chunk's decisions applied
 * ([[07-dashboard]]): *Startseite*, *Heroes* and *Team* are gone from
 * *Seiteninhalte*, *Testimonials* is new.
 */
const route = useRoute();

const main = [
	{ label: 'Kurse', to: { name: 'courses' }, match: ['courses', 'course', 'event'] },
	{ label: 'Experten', to: { name: 'experts' }, match: ['experts', 'expert'] },
	{ label: 'Studenten', to: { name: 'students' }, match: ['students', 'student'] },
];

const overflow = [
	{
		label: 'Backoffice',
		key: 'backoffice',
		children: [
			{ label: 'Rechnungen', to: { name: 'backoffice.invoices' } },
			{ label: 'Exporte', to: { name: 'backoffice.exports' } },
		],
	},
	{ label: 'Rabatt-Codes', to: { name: 'discount-codes' } },
	{
		label: 'Seiteninhalte',
		key: 'content',
		children: [
			{ label: 'Testimonials', to: { name: 'content.testimonials' } },
			{ label: 'News', to: { name: 'content.news' } },
		],
	},
	{ label: 'Einstellungen', to: { name: 'settings' } },
	{ label: 'Mein Profil', to: { name: 'profile' } },
];

const open = ref(false);
const expanded = ref(null);

const isActive = (item) => item.match?.some((prefix) => String(route.name ?? '').split('.')[0] === prefix);
const inGroup = (group) => String(route.name ?? '').startsWith(`${group.key}.`);

// Legacy closes the panel and its submenus on every navigation.
watch(() => route.fullPath, () => {
	open.value = false;
	expanded.value = null;
});

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
</script>

<template>
	<header class="mb-24 min-h-48 pt-16 sm:min-h-64 sm:pt-28 lg:mb-28 lg:min-h-80">
		<div class="grid min-h-[inherit] grid-cols-12 gap-x-16 sm:border-b sm:border-black lg:gap-x-40">
			<div class="col-span-12 sm:col-span-4">
				<a href="/" title="Zur Website" class="text-black">
					<IconLogo />
				</a>
			</div>

			<div class="col-span-12 sm:col-span-8">
				<nav class="flex justify-between" aria-label="Dashboard">
					<ul class="flex text-3xl font-bold sm:text-lg lg:text-2xl">
						<li v-for="item in main" :key="item.label" class="mr-48 flex last:mr-0">
							<RouterLink :to="item.to" class="transition-colors duration-100 ease-in hover:text-teal" :class="{ 'text-teal': isActive(item) }">
								{{ item.label }}
							</RouterLink>
						</li>
					</ul>

					<ul class="flex">
						<li class="mr-24 flex">
							<RouterLink :to="{ name: 'profile' }" title="Mein Profil" class="block text-teal hover:text-black">
								<IconProfile class="h-20 w-auto!" />
							</RouterLink>
						</li>
						<li class="flex">
							<button type="button" class="block h-20 w-24 hover:text-teal" aria-label="Menü" :aria-expanded="open" @click="open = !open">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 20" class="block h-20 w-24" aria-hidden="true">
									<path fill="currentColor" d="M2 1.9h20v2H2v-2zm0 7h20v2H2v-2zm0 7h20v2H2v-2z" />
								</svg>
							</button>
						</li>
					</ul>
				</nav>
			</div>
		</div>

		<!-- `.site-menu__overflow-items`: right edge on the column's —
		     `calc((100% - 1100px) / 2)` at desktop, 16px below it. Legacy pads
		     it 20px and its cross is an inline link whose line box adds 2 more;
		     22px puts every item where production has it. -->
		<div
			v-show="open"
			class="fixed top-0 right-16 z-[501] h-full w-240 border-l border-black bg-white pt-22 lg:right-[calc((100%-1100px)/2)]"
			@keydown.esc="open = false"
		>
			<ul class="mr-16 flex flex-col items-end text-3xl font-bold sm:text-lg lg:text-2xl">
				<li class="mb-8">
					<button type="button" class="block py-8 hover:text-teal" aria-label="Menü schliessen" @click="open = false">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="block size-20" aria-hidden="true">
							<path fill="currentColor" d="M17.6 1L10 8.6 2.4 1 1 2.4 8.6 10 1 17.6 2.3 19l7.6-7.7 7.7 7.7 1.4-1.4-7.7-7.7 7.6-7.6z" />
						</svg>
					</button>
				</li>

				<li v-for="item in overflow" :key="item.label" class="text-right">
					<template v-if="item.children">
						<button
							type="button"
							class="block w-full py-8 text-right hover:text-teal"
							:class="{ 'text-teal': expanded === item.key || inGroup(item) }"
							:aria-expanded="expanded === item.key || inGroup(item)"
							@click="expanded = expanded === item.key ? null : item.key"
						>
							{{ item.label }}
						</button>
						<ul v-if="expanded === item.key || inGroup(item)">
							<li v-for="child in item.children" :key="child.label">
								<RouterLink :to="child.to" class="block py-8 font-normal hover:text-teal" active-class="text-teal">
									{{ child.label }}
								</RouterLink>
							</li>
						</ul>
					</template>
					<RouterLink v-else :to="item.to" class="block py-8 hover:text-teal" active-class="text-teal">{{ item.label }}</RouterLink>
				</li>

				<li>
					<form method="POST" action="/logout">
						<input type="hidden" name="_token" :value="csrf" />
						<button type="submit" class="block py-8 font-normal hover:text-teal">Logout</button>
					</form>
				</li>
			</ul>
		</div>
	</header>
</template>
