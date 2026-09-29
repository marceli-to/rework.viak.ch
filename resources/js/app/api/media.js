import client from './client';

/**
 * The images of whatever owns them — a course, an expert ([[07-dashboard]]).
 * `owner` is its path: `courses/{uuid}`, `experts/{uuid}`. Every action saves
 * at once.
 */
export const fetchMedia = (owner) => client.get(`/admin/${owner}/media`).then((r) => r.data.data);

export const uploadMedia = (owner, file, onProgress) => {
	const body = new FormData();
	body.append('file', file);

	return client
		.post(`/admin/${owner}/media`, body, {
			onUploadProgress: (event) => event.total && onProgress?.(Math.round((event.loaded / event.total) * 100)),
		})
		.then((r) => r.data.data);
};

export const orderMedia = (owner, uuids) => client.patch(`/admin/${owner}/media/order`, { media: uuids });
export const updateMedia = (uuid, fields) => client.put(`/admin/media/${uuid}`, fields).then((r) => r.data.data);
export const setMediaRole = (uuid, role) => client.patch(`/admin/media/${uuid}/role`, { role }).then((r) => r.data.data);
export const cropMedia = (uuid, crop) => client.patch(`/admin/media/${uuid}/crop`, crop).then((r) => r.data.data);
export const deleteMedia = (uuid) => client.delete(`/admin/media/${uuid}`);
