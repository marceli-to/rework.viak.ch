import client from './client';

export const fetchDiscountCodes = () => client.get('/admin/discount-codes').then((r) => r.data.data);
export const fetchDiscountCode = (uuid) => client.get(`/admin/discount-codes/${uuid}`).then((r) => r.data.data);
export const saveDiscountCode = (uuid, form) =>
	(uuid ? client.put(`/admin/discount-codes/${uuid}`, form) : client.post('/admin/discount-codes', form)).then((r) => r.data.data);
export const deleteDiscountCode = (uuid) => client.delete(`/admin/discount-codes/${uuid}`);
