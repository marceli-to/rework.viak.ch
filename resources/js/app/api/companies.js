import client from './client';

/** *Firmen* ([[16-firmenkurse]]): who a Firmenkurs is billed to. */
export const fetchCompanies = () => client.get('/admin/companies').then((r) => r.data.data);
export const fetchCompany = (uuid) => client.get(`/admin/companies/${uuid}`).then((r) => r.data.data);
export const saveCompany = (uuid, form) => (uuid ? client.put(`/admin/companies/${uuid}`, form) : client.post('/admin/companies', form)).then((r) => r.data.data);
export const deleteCompany = (uuid) => client.delete(`/admin/companies/${uuid}`);
