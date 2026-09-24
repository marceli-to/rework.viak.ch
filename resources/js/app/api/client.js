import axios from 'axios';
import { done, start } from '@/composables/useProgress';

/**
 * Session-cookie auth against the same endpoints the public site uses.
 * Errors are normalised here so views never unpack an axios error shape, and
 * every request runs the bar across the top while it is out.
 */
const client = axios.create({
	baseURL: '/api',
	headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
	withCredentials: true,
});

client.interceptors.request.use((config) => {
	start();
	return config;
});

client.interceptors.response.use(
	(response) => {
		done();
		return response;
	},
	(error) => {
		done();
		const { response } = error;

		return Promise.reject({
			status: response?.status ?? 0,
			message: response?.data?.message ?? 'Die Verbindung zum Server ist fehlgeschlagen.',
			errors: response?.data?.errors ?? {},
		});
	},
);

export default client;
