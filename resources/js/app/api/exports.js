import client from './client';

/**
 * Fetched through the client, not linked: the request carries the dashboard's
 * session the way every other one does, and the top bar runs while the
 * workbook is built. The browser saves it under the server's file name.
 */
export async function downloadCourseExport() {
	const response = await client.get('/admin/exports/courses', { responseType: 'blob' });
	const name = /filename="([^"]+)"/.exec(response.headers['content-disposition'] ?? '')?.[1] ?? 'viak-kurse.xlsx';
	const url = URL.createObjectURL(response.data);
	const link = Object.assign(document.createElement('a'), { href: url, download: name });

	link.click();
	URL.revokeObjectURL(url);
}
