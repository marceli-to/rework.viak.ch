import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
	plugins: [
		laravel({
			input: [
				'resources/css/app.css',
				'resources/js/app/app.js',
				'resources/js/site/site.js',
			],
			refresh: true,
		}),
		tailwindcss(),
		vue({
			template: {
				transformAssetUrls: { base: null, includeAbsolute: false },
			},
		}),
	],
	resolve: {
		alias: {
			'@': fileURLToPath(new URL('./resources/js/app', import.meta.url)),
			'@site': fileURLToPath(new URL('./resources/js/site', import.meta.url)),
			'@lang': fileURLToPath(new URL('./lang', import.meta.url)),
		},
	},
	server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
