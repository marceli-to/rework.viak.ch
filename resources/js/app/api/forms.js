import client from './client';

/** A form's schema — its fields and a new record's values ([[Field]]). */
export const fetchForm = (name) => client.get(`/admin/forms/${name}`).then((r) => r.data.data);
