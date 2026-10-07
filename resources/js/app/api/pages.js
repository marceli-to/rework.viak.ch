import client from './client';

/** A fixed page's testimonials ([[Page]]): what it shows, and every one there is to pick from. */
export const fetchPageTestimonials = (page) => client.get(`/admin/pages/${page}/testimonials`).then((r) => r.data.data);
export const savePageTestimonials = (page, uuids) => client.put(`/admin/pages/${page}/testimonials`, { testimonials: uuids });

/** A fixed page's editable copy ([[PageContentController]]); the record's `uuid` is the page's key. */
export const fetchPageContent = (page) => client.get(`/admin/pages/${page}/content`).then((r) => r.data.data);
export const savePageContent = (page, form) => client.put(`/admin/pages/${page}/content`, form).then((r) => r.data.data);
