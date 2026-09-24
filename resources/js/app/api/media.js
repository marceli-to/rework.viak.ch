import client from './client';

/** A course's images ([[07-dashboard]]). Every action saves at once. */
export const fetchCourseMedia = (course) => client.get(`/admin/courses/${course}/media`).then((r) => r.data.data);

export const uploadCourseMedia = (course, file, onProgress) => {
	const body = new FormData();
	body.append('file', file);

	return client
		.post(`/admin/courses/${course}/media`, body, {
			onUploadProgress: (event) => event.total && onProgress?.(Math.round((event.loaded / event.total) * 100)),
		})
		.then((r) => r.data.data);
};

export const orderCourseMedia = (course, uuids) => client.patch(`/admin/courses/${course}/media/order`, { media: uuids });
export const updateMedia = (uuid, fields) => client.put(`/admin/media/${uuid}`, fields).then((r) => r.data.data);
export const setMediaRole = (uuid, role) => client.patch(`/admin/media/${uuid}/role`, { role }).then((r) => r.data.data);
export const cropMedia = (uuid, crop) => client.patch(`/admin/media/${uuid}/crop`, crop).then((r) => r.data.data);
export const deleteMedia = (uuid) => client.delete(`/admin/media/${uuid}`);
