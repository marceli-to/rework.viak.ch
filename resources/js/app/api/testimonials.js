import client from './client';

export const fetchTestimonials = () => client.get('/admin/testimonials').then((r) => r.data.data);
export const fetchTestimonial = (uuid) => client.get(`/admin/testimonials/${uuid}`).then((r) => r.data.data);
export const saveTestimonial = (uuid, form) =>
	(uuid ? client.put(`/admin/testimonials/${uuid}`, form) : client.post('/admin/testimonials', form)).then((r) => r.data.data);
export const deleteTestimonial = (uuid) => client.delete(`/admin/testimonials/${uuid}`);
