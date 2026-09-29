import client from './client';

/** A page of the active students, or every deactivated one, matching `search`. */
export const fetchStudents = ({ search = '', page = 1, deactivated = false } = {}) =>
	client.get('/admin/students', { params: { suche: search || undefined, page, deaktiviert: deactivated ? 1 : undefined } }).then((r) => r.data);

export const fetchStudent = (uuid) => client.get(`/admin/students/${uuid}`).then((r) => r.data.data);
export const saveStudent = (uuid, form) => (uuid ? client.put(`/admin/students/${uuid}`, form) : client.post('/admin/students', form)).then((r) => r.data.data);
export const setStudentActive = (uuid, active) => client.patch(`/admin/students/${uuid}/state`, { active }).then((r) => r.data.data);

/** A student's own page: the address, the seats in three lists, the documents ([[StudentPageController]]). */
export const fetchStudentPage = (uuid) => client.get(`/admin/students/${uuid}/page`).then((r) => r.data.data);

/** *Annullieren*, with the admin's answer to whether the cost is charged (#14). */
export const cancelBooking = (booking, chargePenalty) =>
	client.patch(`/admin/bookings/${booking}/cancel`, { charge_penalty: chargePenalty }).then((r) => r.data.data);
