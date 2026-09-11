import client from './client';

export const fetchEvents = (params = {}) => client.get('/events', { params }).then((r) => r.data.data);
export const setEventState = (uuid, state) =>
	client.patch(`/events/${uuid}/state`, { state }).then((r) => r.data.data);
