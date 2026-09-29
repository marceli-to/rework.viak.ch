/**
 * The settings lists, in legacy's order, with what one of each is called
 * ([[07-dashboard]], step 6). Software is not among them: chunk 05 gives it a
 * screen of its own.
 */
export const KINDS = {
	categories: { title: 'Kategorien', noun: 'Kategorie', schema: 'term' },
	languages: { title: 'Sprachen', noun: 'Sprache', schema: 'term' },
	levels: { title: 'Levels', noun: 'Level', schema: 'term' },
	tags: { title: 'Tags', noun: 'Tag', schema: 'term' },
	locations: { title: 'Orte', noun: 'Ort', schema: 'location' },
};

/** Where a term is used: courses for a term, course dates for a place. */
export const usage = (kind, count) => {
	if (!count) return 'Nicht verwendet';
	const [one, many] = kind === 'locations' ? ['Kursdatum', 'Kursdaten'] : ['Kurs', 'Kursen'];
	return count === 1 ? `In einem ${one}` : `In ${count} ${many}`;
};
