import client from './client';

/** A page of one list, `offen` or `versendet`, matching `search` ([[LicenceOrderController]]). */
export const fetchOrders = ({ status, search = '', page = 1 }) =>
	client.get('/admin/licence-orders', { params: { status, suche: search || undefined, page } }).then((r) => r.data);

export const fetchOrder = (uuid) => client.get(`/admin/licence-orders/${uuid}`).then((r) => r.data.data);

/** The entry form's customer, addresses and catalogue. */
export const fetchOrderForm = (customer) => client.get(`/admin/customers/${customer}/licence-orders/create`).then((r) => r.data.data);
export const placeOrder = (customer, form) => client.post(`/admin/customers/${customer}/licence-orders`, form).then((r) => r.data.data);

/** *Versendet*, per line, and back. Answers with the whole order. */
export const setDispatched = (item, dispatched) => client.patch(`/admin/licence-order-items/${item}/dispatch`, { dispatched }).then((r) => r.data.data);
