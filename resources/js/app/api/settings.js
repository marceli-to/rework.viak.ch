import client from './client';

/** Every settings list at once: categories, languages, levels, tags, locations. */
export const fetchSettings = () => client.get('/admin/settings').then((r) => r.data.data);

/** One kind's calls, for [[ResourceForm]]. */
export const settingsOf = (kind) => ({
	load: (uuid) => client.get(`/admin/settings/${kind}/${uuid}`).then((r) => r.data.data),
	save: (uuid, form) => (uuid ? client.put(`/admin/settings/${kind}/${uuid}`, form) : client.post(`/admin/settings/${kind}`, form)).then((r) => r.data.data),
	remove: (uuid) => client.delete(`/admin/settings/${kind}/${uuid}`),
});
