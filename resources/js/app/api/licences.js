import client from './client';

export const fetchLicences = () => client.get('/admin/licences').then((r) => r.data.data);
export const fetchLicence = (uuid) => client.get(`/admin/licences/${uuid}`).then((r) => r.data.data);
export const saveLicence = (uuid, form) =>
	(uuid ? client.put(`/admin/licences/${uuid}`, form) : client.post('/admin/licences', form)).then((r) => r.data.data);
export const deleteLicence = (uuid) => client.delete(`/admin/licences/${uuid}`);

/** A product's variants, each saved on its own ([[LicenceVariantController]]). */
export const fetchVariants = (product) => client.get(`/admin/licences/${product}/variants`).then((r) => r.data.data);
export const fetchVariant = (uuid) => client.get(`/admin/licence-variants/${uuid}`).then((r) => r.data.data);
export const saveVariant = (uuid, form, product) =>
	(uuid ? client.put(`/admin/licence-variants/${uuid}`, form) : client.post(`/admin/licences/${product}/variants`, form)).then((r) => r.data.data);
export const saveVariantOrder = (product, uuids) => client.post(`/admin/licences/${product}/variants/order`, { variants: uuids });
export const deleteVariant = (uuid) => client.delete(`/admin/licence-variants/${uuid}`);
