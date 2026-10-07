import client from './client';

export const fetchProjects = () => client.get('/admin/projects').then((r) => r.data.data);
export const fetchProject = (uuid) => client.get(`/admin/projects/${uuid}`).then((r) => r.data.data);
export const saveProject = (uuid, form) =>
	(uuid ? client.put(`/admin/projects/${uuid}`, form) : client.post('/admin/projects', form)).then((r) => r.data.data);
export const saveProjectOrder = (uuids) => client.post('/admin/projects/order', { projects: uuids });
export const deleteProject = (uuid) => client.delete(`/admin/projects/${uuid}`);
