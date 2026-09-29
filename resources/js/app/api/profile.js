import client from './client';

/** The signed-in admin's own details ([[ProfileController]]). */
export const fetchProfile = () => client.get('/admin/profile').then((r) => r.data.data);
export const saveProfile = (_uuid, form) => client.put('/admin/profile', form).then((r) => r.data.data);
