import client from './client';

/** A page of one list — `offen`, `faellig`, `bezahlt`, `storniert` — matching `search`. */
export const fetchInvoices = ({ status, search = '', page = 1 }) =>
	client.get('/admin/invoices', { params: { status, suche: search || undefined, page } }).then((r) => r.data);

export const fetchInvoice = (uuid) => client.get(`/admin/invoices/${uuid}`).then((r) => r.data.data);
export const saveInvoice = (uuid, form) => client.put(`/admin/invoices/${uuid}`, form).then((r) => r.data.data);
