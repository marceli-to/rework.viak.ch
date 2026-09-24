import client from './client';

/** The whole catalogue, each course with its upcoming dates. */
export const fetchCourses = () => client.get('/admin/courses').then((r) => r.data.data);

/** The accordion's new order, as the full list of uuids. */
export const saveCourseOrder = (uuids) => client.post('/admin/courses/order', { courses: uuids });
