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

/** *Veranstaltung abschliessen*, with the seats that attended ([[EventPageController::close]]). */
export const closeEvent = (event, attended) => client.post(`/admin/events/${event}/close`, { attended }).then((r) => r.data.data);

/** A seat missed at closing: attended after all, and its confirmation sent ([[EventPageController::confirm]]). */
export const confirmAttendance = (event, booking) => client.post(`/admin/events/${event}/bookings/${booking}/confirm`).then((r) => r.data.data);

/** *Teilnehmer hinzufügen*: book a student onto the date ([[CreateBookingForUser]]). */
export const bookStudent = (event, student) => client.post(`/admin/events/${event}/bookings`, { student }).then((r) => r.data.data);

/** A note to everyone on the course, with its files, in one multipart POST. */
export function postMessage(event, { subject, body, copyToMe, attachments }) {
	const form = new FormData();
	form.append('subject', subject);
	form.append('body', body);
	form.append('body_format', 'html');
	form.append('copy_to_me', copyToMe ? '1' : '0');
	attachments.forEach((file) => form.append('attachments[]', file));

	return client.post(`/admin/events/${event}/messages`, form).then((r) => r.data.data);
}

/** *Kurs-Dokumente*: upload, each with its *Bezeichnung* (`[{ file, caption }]`), and remove one again. */
export function uploadFiles(event, items) {
	const form = new FormData();
	items.forEach(({ file, caption }) => {
		form.append('files[]', file);
		form.append('captions[]', caption ?? '');
	});

	return client.post(`/admin/events/${event}/files`, form).then((r) => r.data.data);
}

export const removeFile = (event, file) => client.delete(`/admin/events/${event}/files/${file}`);

/** *Teilnehmerliste (PDF)*, fetched as the Excel export is, and saved under the server's name. */
export async function downloadParticipants(event) {
	const response = await client.get(`/admin/events/${event}/participants`, { responseType: 'blob' });
	const name = /filename="([^"]+)"/.exec(response.headers['content-disposition'] ?? '')?.[1] ?? 'teilnehmerliste.pdf';
	const url = URL.createObjectURL(response.data);
	const link = Object.assign(document.createElement('a'), { href: url, download: name });

	link.click();
	URL.revokeObjectURL(url);
}
