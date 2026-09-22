import Alpine from 'alpinejs';

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
 * count in the header and the basket page itself. The **dialogs** live here for
 * the same reason — legacy renders a `<notification>` pair inside every
 * `basket-button`, so a course page with four events carries eight hidden
 * modals; one basket means one of each.
 */
export default {
	items: [],
	code: null,
	pricing: null,
	loading: false,
	error: null,

	/**
	 * Whether anyone is signed in, from the `<meta>` the layout writes.
	 *
	 * **Pricing is behind the session guard and that is deliberate**, on both
	 * sites. Legacy leaves `PUT /basket/{event}` open to a guest and puts
	 * `GET /basket` behind `auth:sanctum + verified + role:student`, so a
	 * visitor can fill a basket and cannot see what it costs until they log in;
	 * the rework's `/api/basket/price` is the same line in the same place, and
	 * `BookingApiTest` pins it.
	 *
	 * So the fix for a guest is not to open the endpoint — it is to not ask.
	 * Before this, `add()` and `init()` called `price()` regardless and set
	 * `error` to `Unauthenticated.` on every guest click, which was invisible
	 * only because nothing rendered `error` yet ([[09-public-site]]).
	 */
	authenticated: false,

	/** The uuid waiting on the rental question, or null when nothing is. */
	rentalFor: null,

	/** The confirmation that follows an add. */
	confirmed: false,

	init() {
		this.authenticated =
			document.querySelector('meta[name="authenticated"]')?.content === '1';

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

	/**
	 * *Buchen*, which is not always an add.
	 *
	 * `Basket.vue` renders two different buttons off `hasRentals`: one that adds
	 * straight away, and one that asks about the laptop first. The question has
	 * to come before the add, because the rental is frozen onto the booking
	 * along with its price ([[PriceBasket]]).
	 */
	book(uuid, rentalsAvailable = false) {
		if (this.has(uuid)) return;

		if (rentalsAvailable) {
			this.rentalFor = uuid;
			return;
		}

		return this.add(uuid, false);
	},

	/** *Ja gerne* / *Nein, ich bringe meinen eigenen Laptop*. */
	answerRental(rental) {
		const uuid = this.rentalFor;
		this.rentalFor = null;

		if (!uuid) return;

		return this.add(uuid, rental);
	},

	/**
	 * Escape, or a click on the backdrop. Nothing is added — which is the same
	 * outcome as *Nein* only in price, not in meaning, so it does not confirm.
	 */
	cancelRental() {
		this.rentalFor = null;
	},

	add(uuid, rental = false) {
		if (this.has(uuid)) return;
		this.items.push({ event: uuid, rental });
		this.persist();
		this.confirmed = true;

		return this.price();
	},

	remove(uuid) {
		this.items = this.items.filter((item) => item.event !== uuid);
		this.persist();

		Alpine.store('toast').show('Der Kurs wurde aus dem Warenkorb gelöscht.');

		return this.items.length ? this.price() : this.clearPricing();
	},

	/**
	 * The laptop on its own, which the basket page drops without dropping the
	 * course — legacy's `removeRentalFromBasket`, and it says so too.
	 */
	setRental(uuid, rental) {
		const item = this.items.find((i) => i.event === uuid);
		if (!item || item.rental === rental) return;

		item.rental = rental;
		this.persist();

		if (!rental) {
			Alpine.store('toast').show('Der Mietcomputer wurde aus dem Warenkorb gelöscht.');
		}

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

		// A guest has nothing to be told: every screen that shows a price is
		// behind the login. See `authenticated`, above.
		if (!this.authenticated) return this.clearPricing();

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
