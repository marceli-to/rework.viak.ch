import client from './client';

/** The signed-in admin's OAuth connections, the claude.ai connector ([[ConnectedAppController]]). */
export const fetchConnectedApps = () => client.get('/admin/profile/connected-apps').then((r) => r.data.data);
export const disconnectApp = (id) => client.delete(`/admin/profile/connected-apps/${id}`);
