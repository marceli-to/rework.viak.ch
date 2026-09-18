/**
 * The basket, as far as the browser is concerned ([[06-bookings]]).
 *
 * **It holds selections, never prices.** Every figure shown comes from
 * `POST /api/basket/price`, because the server is the pricing authority — that
 * is the rule chunk 06 was built around, and legacy broke it in three separate
 * ways: the basket screen and `Booking::create()` applied a fixed code
 * differently, nothing clamped a discount to the fee, and an expired code became
 * a silent zero.
 *
 * A store rather than component state because two places read it at once: the
 * count in the header and the basket page itself.
 */
export default {
	items: [],
	code: null,
	pricing: null,
	loading: false,
	error: null,

	init() {
		// Per-viewer convenience only. What is *charged* is priced server-side at
		// checkout, so a stale or tampered basket costs nothing: the worst case
		// is a course that no longer exists, which the till refuses.
		try {
			const saved = window.localStorage.getItem('viak.basket');
			if (saved) {
				const { items, code } = JSON.parse(saved);
				this.items = Array.isArray(items) ? items : [];
				this.code = code ?? null;
			}
		} catch {
			// Private windows and blocked site data throw here. An empty basket
			// is a perfectly good outcome.
		}

		if (this.items.length) this.price();
	},

	get count() {
		return this.items.length;
	},

	has(uuid) {
		return this.items.some((item) => item.event === uuid);
	},

	add(uuid, rental = false) {
		if (this.has(uuid)) return;
		this.items.push({ event: uuid, rental });
		this.persist();
		return this.price();
	},

	remove(uuid) {
		this.items = this.items.filter((item) => item.event !== uuid);
		this.persist();
		return this.items.length ? this.price() : this.clearPricing();
	},

	setRental(uuid, rental) {
		const item = this.items.find((i) => i.event === uuid);
		if (!item) return;
		item.rental = rental;
		this.persist();
		return this.price();
	},

	applyCode(code) {
		this.code = code || null;
		this.persist();
		return this.price();
	},

	clear() {
		this.items = [];
		this.code = null;
		this.persist();
		this.clearPricing();
	},

	clearPricing() {
		this.pricing = null;
		this.error = null;
	},

	/** Ask the server what this costs. The only source of a number on screen. */
	async price() {
		if (!this.items.length) return this.clearPricing();

		this.loading = true;
		this.error = null;

		try {
			const response = await fetch('/api/basket/price', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
				body: JSON.stringify({ items: this.items, code: this.code }),
			});

			const body = await response.json();

			if (!response.ok) {
				// A code that will not apply is shown, never swallowed. Legacy's
				// `Discount::apply()` returned FALSE, which became a 0 discount,
				// so the customer saw a discounted basket and paid full price.
				this.error = body.message ?? 'Der Warenkorb konnte nicht berechnet werden.';
				this.pricing = null;
				return;
			}

			this.pricing = body.data;
		} catch {
			this.error = 'Der Warenkorb konnte nicht berechnet werden.';
		} finally {
			this.loading = false;
		}
	},

	persist() {
		try {
			window.localStorage.setItem(
				'viak.basket',
				JSON.stringify({ items: this.items, code: this.code }),
			);
		} catch {
			// See init(): storage can be unavailable, and the basket still works.
		}
	},
};
