/**
 * The basket page's list ([[09-public-site]]).
 *
 * **The rows are drawn in the browser, and that is the one place on this site
 * where that is right.** Everything else here is server-rendered Blade; the
 * basket cannot be, because the selection lives in `localStorage` and the
 * server does not know it until the next step posts it. So the page asks
 * `/api/basket/price` — the endpoint `00-foundation.md` kept for exactly this —
 * and `<template x-for>` renders what comes back. The markup still lives in the
 * template rather than in a string here.
 *
 * Nothing on this page computes a price. Every figure is `course_fee`,
 * `rental_fee` or `total` as the server returned it.
 */
export default () => ({
	get basket() {
		return this.$store.basket;
	},

	/** Whether the answer has arrived, so an empty basket is not claimed early. */
	get settled() {
		return !this.basket.loading && (this.basket.pricing !== null || !this.basket.count);
	},

	get items() {
		return this.basket.pricing?.items ?? [];
	},

	/** The priced basket, or null until the server has answered. */
	get pricing() {
		return this.basket.pricing;
	},

	/**
	 * `15. Oktober 2026`, which is `translatedFormat('d. F Y')` — legacy's
	 * `EventDate::getDateLongAttribute()`, and the same string the course
	 * page's event row renders server-side.
	 *
	 * `2-digit` rather than `numeric` because Carbon's `d` is zero-padded:
	 * `numeric` would give `5. Oktober` where the rest of the site says `05.`.
	 */
	dateLong(value) {
		return new Intl.DateTimeFormat('de-CH', {
			day: '2-digit',
			month: 'long',
			year: 'numeric',
		}).format(new Date(`${value}T00:00:00`));
	},

	/** `08:30:00` in the column, `08.30` on the page — the site's separator. */
	time(value) {
		return value ? value.slice(0, 5).replace(':', '.') : '';
	},

	/** The experts as one phrase, as `StackedListEvent.vue` prints them. */
	experts(event) {
		return (event.experts ?? []).map((expert) => expert.name).join(', ');
	},

	location(event) {
		return event.location?.description?.de ?? '';
	},
});
