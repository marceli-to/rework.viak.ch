import client from './client';

/** A page of the active customers, or every deactivated one, matching `search`. */
export const fetchCustomers = ({ search = '', page = 1, deactivated = false } = {}) =>
	client.get('/admin/customers', { params: { suche: search || undefined, page, deaktiviert: deactivated ? 1 : undefined } }).then((r) => r.data);

export const fetchCustomer = (uuid) => client.get(`/admin/customers/${uuid}`).then((r) => r.data.data);
export const saveCustomer = (uuid, form) => (uuid ? client.put(`/admin/customers/${uuid}`, form) : client.post('/admin/customers', form)).then((r) => r.data.data);
export const setCustomerActive = (uuid, active) => client.patch(`/admin/customers/${uuid}/state`, { active }).then((r) => r.data.data);

/** A customer's own page: the address, the seats in three lists, the documents ([[CustomerPageController]]). */
export const fetchCustomerPage = (uuid) => client.get(`/admin/customers/${uuid}/page`).then((r) => r.data.data);

/** *Annullieren*, with the admin's answer to whether the cost is charged (#14). */
export const cancelBooking = (booking, chargePenalty) =>
	client.patch(`/admin/bookings/${booking}/cancel`, { charge_penalty: chargePenalty }).then((r) => r.data.data);
