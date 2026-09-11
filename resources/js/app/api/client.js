import axios from 'axios';

/**
 * Session-cookie auth against the same endpoints the public site uses.
 * Errors are normalised here so views never unpack an axios error shape.
 */
const client = axios.create({
	baseURL: '/api',
	headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
	withCredentials: true,
});

client.interceptors.response.use(
	(response) => response,
	(error) => {
		const { response } = error;

		return Promise.reject({
			status: response?.status ?? 0,
			message: response?.data?.message ?? 'Die Verbindung zum Server ist fehlgeschlagen.',
			errors: response?.data?.errors ?? {},
		});
	},
);

export default client;
