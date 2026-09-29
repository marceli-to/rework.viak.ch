import client from './client';

export const fetchExperts = () => client.get('/admin/experts').then((r) => r.data.data);
export const fetchExpert = (uuid) => client.get(`/admin/experts/${uuid}`).then((r) => r.data.data);
export const saveExpert = (uuid, form) => (uuid ? client.put(`/admin/experts/${uuid}`, form) : client.post('/admin/experts', form)).then((r) => r.data.data);
export const saveExpertOrder = (uuids) => client.post('/admin/experts/order', { experts: uuids });
export const deleteExpert = (uuid) => client.delete(`/admin/experts/${uuid}`);
