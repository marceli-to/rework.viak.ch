import client from './client';

/** The whole catalogue, each course with its upcoming dates. */
export const fetchCourses = () => client.get('/admin/courses').then((r) => r.data.data);

/** The accordion's new order, as the full list of uuids. */
export const saveCourseOrder = (uuids) => client.post('/admin/courses/order', { courses: uuids });

/** The form's pickers — the five taxonomies — and the next course number. */
export const fetchCourseOptions = () => client.get('/admin/courses/options').then((r) => r.data.data);

/** One course in the form's own shape — what `saveCourse` sends back. */
export const fetchCourse = (uuid) => client.get(`/admin/courses/${uuid}`).then((r) => r.data.data);

export const saveCourse = (uuid, form) =>
	(uuid ? client.put(`/admin/courses/${uuid}`, form) : client.post('/admin/courses', form)).then((r) => r.data.data);

export const deleteCourse = (uuid) => client.delete(`/admin/courses/${uuid}`);
