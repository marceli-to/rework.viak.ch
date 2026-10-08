import client from './client';

export const fetchLicences = () => client.get('/admin/licences').then((r) => r.data.data);
export const fetchLicence = (uuid) => client.get(`/admin/licences/${uuid}`).then((r) => r.data.data);
export const saveLicence = (uuid, form) =>
	(uuid ? client.put(`/admin/licences/${uuid}`, form) : client.post('/admin/licences', form)).then((r) => r.data.data);
export const deleteLicence = (uuid) => client.delete(`/admin/licences/${uuid}`);
