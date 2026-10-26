/**
 * The header's search ([[15-search]]): the magnifier opens a panel under the
 * header with one field, which asks `/api/search` as it is typed into and
 * lists what it finds. Arrows move through the list, Enter opens the one
 * marked or, with none marked, the result page; Escape or a click outside
 * closes it.
 *
 * One request at a time is enough: an answer that arrives after a newer
 * query was sent is dropped, so a slow *rh* cannot overwrite *rhino*.
 */
export default (resultsUrl) => ({
	open: false,
	query: '',
	hits: [],
	total: 0,
	active: -1,
	loading: false,
	timer: null,
	asked: 0,
	// The query the hits are for: *Nichts gefunden* only once one has been answered.
	answered: '',

	toggle() {
		this.open ? this.close() : this.show();
	},

	show() {
		this.open = true;
		this.$nextTick(() => this.$refs.field.focus());
	},

	close() {
		this.open = false;
		this.active = -1;
	},

	type() {
		clearTimeout(this.timer);
		this.active = -1;

		if (this.query.trim().length < 2) {
			this.hits = [];
			this.total = 0;
			this.answered = '';
			return;
		}

		this.timer = setTimeout(() => this.ask(), 180);
	},

	async ask() {
		const asked = ++this.asked;
		this.loading = true;

		try {
			const response = await fetch(`/api/search?limit=6&q=${encodeURIComponent(this.query.trim())}`, { headers: { Accept: 'application/json' } });
			const { data, total } = await response.json();
			if (asked === this.asked) {
				this.hits = data;
				this.total = total;
				this.answered = this.query.trim();
			}
		} catch {
			if (asked === this.asked) {
				this.hits = [];
				this.total = 0;
			}
		} finally {
			if (asked === this.asked) this.loading = false;
		}
	},

	// -1 is the field itself; down from the last hit goes back to it, and up from it to the last.
	move(step) {
		if (!this.hits.length) return;
		const next = this.active + step;
		this.active = next >= this.hits.length ? -1 : next < -1 ? this.hits.length - 1 : next;
	},

	go() {
		const hit = this.hits[this.active];
		window.location.href = hit ? hit.url : this.resultsFor(this.query);
	},

	// *13 Einträge gefunden*, as the result page says it.
	get found() {
		return this.total === 1 ? '1 Eintrag gefunden' : `${this.total} Einträge gefunden`;
	},

	resultsFor(query) {
		return `${resultsUrl}?q=${encodeURIComponent(query.trim())}`;
	},
});
