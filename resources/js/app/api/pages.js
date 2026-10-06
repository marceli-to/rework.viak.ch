import client from './client';

/** A fixed page's testimonials ([[Page]]): what it shows, and every one there is to pick from. */
export const fetchPageTestimonials = (page) => client.get(`/admin/pages/${page}/testimonials`).then((r) => r.data.data);
export const savePageTestimonials = (page, uuids) => client.put(`/admin/pages/${page}/testimonials`, { testimonials: uuids });
