import client from './client';

/** Confirm, close or cancel a course date — for its edit screen ([[07-dashboard]]). */
export const setEventState = (uuid, state) =>
	client.patch(`/admin/events/${uuid}/state`, { state }).then((r) => r.data.data);
