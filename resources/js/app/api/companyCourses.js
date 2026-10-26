import client from './client';

/** *Firmenkurse* ([[16-firmenkurse]]): the list is whole, a handful a year. */
export const fetchCompanyCourses = () => client.get('/admin/company-courses').then((r) => r.data.data);
export const fetchCompanyCourse = (uuid) => client.get(`/admin/company-courses/${uuid}`).then((r) => r.data.data);
export const saveCompanyCourse = (uuid, form) =>
	(uuid ? client.put(`/admin/company-courses/${uuid}`, form) : client.post('/admin/company-courses', form)).then((r) => r.data.data);
export const deleteCompanyCourse = (uuid) => client.delete(`/admin/company-courses/${uuid}`);

/** *Rechnung stellen* and *Rechnung stornieren* ([[CompanyCourseController]]). */
export const invoiceCompanyCourse = (uuid) => client.post(`/admin/company-courses/${uuid}/invoice`).then((r) => r.data.data);
export const withdrawCompanyCourseInvoice = (uuid) => client.patch(`/admin/company-courses/${uuid}/invoice/withdraw`).then((r) => r.data.data);
