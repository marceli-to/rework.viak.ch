/**
 * The settings lists, in legacy's order, with what one of each is called
 * ([[07-dashboard]], step 6). *Software* and *Hersteller* follow since
 * chunk 05 ([[05-licences]]): what licence products (and courses) belong
 * to, and their makers. `home` puts a list on that page instead of here
 * (Marcel, 2026-10-08).
 */
export const KINDS = {
	categories: { title: 'Kategorien', noun: 'Kategorie', schema: 'term' },
	languages: { title: 'Sprachen', noun: 'Sprache', schema: 'term' },
	levels: { title: 'Levels', noun: 'Level', schema: 'term' },
	tags: { title: 'Tags', noun: 'Tag', schema: 'term' },
	locations: { title: 'Orte', noun: 'Ort', schema: 'location' },
	software: { title: 'Software', noun: 'Software', schema: 'term', home: 'licences' },
	manufacturers: { title: 'Hersteller', noun: 'Hersteller', schema: 'term', home: 'licences' },
};

const counted = (count, one, many) => `${count} ${count === 1 ? one : many}`;

/**
 * Where a term is used, for its badges: events for a place, courses for a
 * term, and products for a software or a maker.
 */
export const usage = (kind, item) => {
	if (!item.usage) return ['Nicht verwendet'];
	if (kind === 'locations') return [counted(item.usage, 'Veranstaltung', 'Veranstaltungen')];
	return [
		...(item.courses ? [counted(item.courses, 'Kurs', 'Kurse')] : []),
		...(item.licences ? [counted(item.licences, 'Produkt', 'Produkte')] : []),
	];
};
