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
 * Lower-case, accents gone — so *fuhrung* finds *Führung*, the way an admin
 * types into a search box.
 */
export function fold(value) {
	return String(value ?? '')
		.normalize('NFD')
		.replace(/\p{Diacritic}/gu, '')
		.toLowerCase();
}
