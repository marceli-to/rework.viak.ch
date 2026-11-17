import client from './client';

/** *Seiteninhalte → News*, the homepage's news box ([[NewsController]]). */
export const fetchNewsList = () => client.get('/admin/news').then((r) => r.data.data);
export const fetchNews = (uuid) => client.get(`/admin/news/${uuid}`).then((r) => r.data.data);
export const saveNews = (uuid, form) => (uuid ? client.put(`/admin/news/${uuid}`, form) : client.post('/admin/news', form)).then((r) => r.data.data);
export const saveNewsOrder = (uuids) => client.post('/admin/news/order', { news: uuids });
export const deleteNews = (uuid) => client.delete(`/admin/news/${uuid}`);
