/**
 * Dates the way the site writes them — *06. Oktober 2026* — from an ISO day.
 * Parsed as a calendar day, not a moment, so no time zone can move it.
 */
export function longDate(iso) {
	if (!iso) return '';

	const [year, month, day] = iso.split('-').map(Number);

	return new Date(year, month - 1, day).toLocaleDateString('de-CH', { day: '2-digit', month: 'long', year: 'numeric' });
}

/**
 * The short form, *24.09.2026* — where the long one breaks over two lines,
 * as it did in the date column of *Kurse* by date (Marcel, 2026-09-24).
 */
export function shortDate(iso) {
	return iso ? iso.split('-').reverse().join('.') : '';
}

/**
 * Lower-case, accents gone — so *fuhrung* finds *Führung*, the way an admin
 * types into a search box.
 */
export function fold(value) {
	return String(value ?? '')
		.normalize('NFD')
		.replace(/\p{Diacritic}/gu, '')
		.toLowerCase();
}
