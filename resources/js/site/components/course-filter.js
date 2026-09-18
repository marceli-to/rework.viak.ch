/**
 * The course filter ([[02-courses-events]]).
 *
 * Filters client-side over what the page already rendered. Legacy posted to
 * `/api/course/filter` on every click and kept the state in a Vuex store —
 * `CourseFilterStore`, one of the four legacy `Stores/` that `00-foundation.md`
 * says disappear, because this is client state and nothing else wants it.
 *
 * The full list is a few dozen courses, so there is nothing here worth a round
 * trip. The chosen filter is reflected in the query string, which keeps a
 * filtered view linkable — legacy's was not.
 */
export default (initial = {}) => ({
	software: initial.software ?? null,

	init() {
		const params = new URLSearchParams(window.location.search);
		this.software = params.get('software') ?? this.software;
	},

	select(uuid) {
		this.software = this.software === uuid ? null : uuid;

		const url = new URL(window.location.href);
		this.software ? url.searchParams.set('software', this.software) : url.searchParams.delete('software');
		window.history.replaceState({}, '', url);
	},

	matches(list) {
		return !this.software || (list ?? '').split(' ').includes(this.software);
	},
});
