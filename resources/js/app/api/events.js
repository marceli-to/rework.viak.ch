import client from './client';

/** One course date in the form's own shape — what `saveEvent` sends back. */
export const fetchEvent = (uuid) => client.get(`/admin/events/${uuid}`).then((r) => r.data.data);

/** A new date belongs to a course, so it is created under one. */
export const saveEvent = (uuid, form, course) =>
	(uuid ? client.put(`/admin/events/${uuid}`, form) : client.post(`/admin/courses/${course}/events`, form)).then((r) => r.data.data);

export const deleteEvent = (uuid) => client.delete(`/admin/events/${uuid}`);

/** Confirm, close or cancel a course date — for its edit screen ([[07-dashboard]]). */
export const setEventState = (uuid, state) =>
	client.patch(`/admin/events/${uuid}/state`, { state }).then((r) => r.data.data);

/** A course date's own page: the date and its participants ([[EventPageController]]). */
export const fetchEventPage = (uuid) => client.get(`/admin/events/${uuid}/page`).then((r) => r.data.data);

/** Tick a seat as attended, or untick it. */
export const setParticipation = (booking, participated) =>
	client.patch(`/admin/bookings/${booking}/participation`, { participated }).then((r) => r.data.data);
