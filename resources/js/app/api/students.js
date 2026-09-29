import client from './client';

/** A page of the active students, or every deactivated one, matching `search`. */
export const fetchStudents = ({ search = '', page = 1, deactivated = false } = {}) =>
	client.get('/admin/students', { params: { suche: search || undefined, page, deaktiviert: deactivated ? 1 : undefined } }).then((r) => r.data);

export const fetchStudent = (uuid) => client.get(`/admin/students/${uuid}`).then((r) => r.data.data);
export const saveStudent = (uuid, form) => (uuid ? client.put(`/admin/students/${uuid}`, form) : client.post('/admin/students', form)).then((r) => r.data.data);
export const setStudentActive = (uuid, active) => client.patch(`/admin/students/${uuid}/state`, { active }).then((r) => r.data.data);
