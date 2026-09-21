/**
 * The course filter ([[09-public-site]]).
 *
 * **Every published course is in the DOM; the filter hides the ones that do not
 * match.** The server still reads the query string and still renders the result
 * — it just decides which cards carry `hidden` rather than which rows it
 * fetches. So the page is correct before Alpine starts, correct without
 * JavaScript at all, and a crawler sees the whole catalogue on `/de/kurse`.
 *
 * What that buys is the thing this was built for: on a phone the filter is a
 * full-screen panel, and a navigation would close it. Here a selection is a
 * class change, so several attributes can be set in one visit to the panel and
 * `Anzeigen` merely closes it. Desktop gets the same immediacy, which is what
 * legacy's `frontend/filter/Index.vue` had and a query-string link did not.
 *
 * Viable because the catalogue is 32 courses on one page. Add pagination and
 * this is wrong — but the query string still works server-side, so the way back
 * is the controller, not a rewrite.
 *
 * One value per attribute, as legacy has it: choosing the active one clears it.
 * **Unset is the empty string, never null**, because six of the seven controls
 * are `<select>`s — a select's own "no choice" value is `''`, and assigning it
 * `null` leaves it showing nothing at all. `App\Support\CourseFilter` holds the
 * matching rule on the other side of the same uuids.
 */

/*
 * Outside the component on purpose. Alpine wraps component state in a reactive
 * Proxy, and a Map or WeakMap held there throws on `set` — the proxy is not the
 * receiver its methods expect.
 */
const parsed = new WeakMap();

export default (initial = {}) => ({
	/** Whether the phone panel is showing. Irrelevant from `sm` up, where the filter is a column. */
	open: false,

	/** attribute → the chosen value, or `''`. Seeded from what the server filtered by. */
	selected: initial,

	close() {
		this.open = false;
	},

	/** `Anzeigen` has nothing to apply — the list is already filtered. It closes the panel. */
	apply() {
		this.open = false;
	},

	get active() {
		return Object.values(this.selected).some(Boolean);
	},

	/** Drives the count on `Anzeigen` and the empty state. */
	get count() {
		return this.cards().filter((card) => this.matches(card)).length;
	},

	/** The category list: choosing what is already chosen clears it. */
	toggle(attribute, value) {
		this.set(attribute, this.selected[attribute] === value ? '' : value);
	},

	/** The six selects, where the control already carries its own empty choice. */
	set(attribute, value) {
		this.selected[attribute] = value || '';
		this.sync();
	},

	reset() {
		for (const attribute of Object.keys(this.selected)) {
			this.selected[attribute] = '';
		}

		this.sync();
	},

	/**
	 * A card matches when every *chosen* attribute is among the ones it carries.
	 * Reads `selected`, so Alpine re-runs the binding on every card when one
	 * changes.
	 */
	matches(card) {
		const facets = this.facets(card);

		return Object.entries(this.selected).every(
			([attribute, value]) => !value || (facets[attribute] ?? []).includes(value),
		);
	},

	cards() {
		return Array.from(this.$root.querySelectorAll('[data-facets]'));
	},

	facets(card) {
		if (!parsed.has(card)) {
			parsed.set(card, JSON.parse(card.dataset.facets || '{}'));
		}

		return parsed.get(card);
	},

	/**
	 * Keep the URL honest so a filtered view stays linkable — the one property
	 * the server-rendered links had that a client-side filter would otherwise
	 * throw away.
	 *
	 * `replaceState`, not legacy's `pushState`: with push, leaving a page you
	 * filtered four times costs four presses of Back. The URL is shareable
	 * either way.
	 */
	sync() {
		const url = new URL(window.location);

		for (const [attribute, value] of Object.entries(this.selected)) {
			if (value) {
				url.searchParams.set(attribute, value);
			} else {
				url.searchParams.delete(attribute);
			}
		}

		window.history.replaceState({}, '', url);
	},
});
