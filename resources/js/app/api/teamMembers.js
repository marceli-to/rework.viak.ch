import client from './client';

export const fetchTeamMembers = () => client.get('/admin/team-members').then((r) => r.data.data);
export const fetchTeamMember = (uuid) => client.get(`/admin/team-members/${uuid}`).then((r) => r.data.data);
export const saveTeamMember = (uuid, form) =>
	(uuid ? client.put(`/admin/team-members/${uuid}`, form) : client.post('/admin/team-members', form)).then((r) => r.data.data);
export const saveTeamMemberOrder = (uuids) => client.post('/admin/team-members/order', { team_members: uuids });
export const deleteTeamMember = (uuid) => client.delete(`/admin/team-members/${uuid}`);
