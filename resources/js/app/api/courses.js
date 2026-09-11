import client from './client';

export const fetchCourses = () => client.get('/courses').then((r) => r.data.data);
export const fetchCourse = (uuid) => client.get(`/courses/${uuid}`).then((r) => r.data.data);
export const updateCourse = (uuid, payload) => client.put(`/courses/${uuid}`, payload).then((r) => r.data.data);
