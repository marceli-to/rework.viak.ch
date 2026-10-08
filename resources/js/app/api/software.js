import client from './client';

/** A software's page, the level above the products ([[SoftwareController]]). */
export const fetchSoftware = (uuid) => client.get(`/admin/software/${uuid}`).then((r) => r.data.data);
export const saveSoftware = (uuid, form) =>
	(uuid ? client.put(`/admin/software/${uuid}`, form) : client.post('/admin/software', form)).then((r) => r.data.data);
export const deleteSoftware = (uuid) => client.delete(`/admin/software/${uuid}`);
