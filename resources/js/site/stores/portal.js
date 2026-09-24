/**
 * The three things a student can do to a booking from the portal
 * ([[08-accounts]], [[09-public-site]]).
 *
 * Legacy's `shared/mixins/Booking.js`, which the student portal and the booked-
 * event screen both mix in: cancel a booking, cancel its laptop, add a laptop.
 * Each opens a confirmation first — except the add, which legacy fires
 * immediately, and that is kept: it is the only one of the three that is free
 * to undo.
 *
 * A **store** rather than a component, for the reason `x-dialog.basket`
 * is one: the dialogs are rendered once per page and every row talks to the same
 * pair. Legacy renders a `<notification>` inside every row.
 *
 * Nothing here computes a penalty. The amount and the rate are rendered into the
 * row by the server from [[CancellationPenalty]], which is the same class that
 * will raise the invoice — so what the dialog promises and what arrives cannot
 * drift.
 */
import Alpine from 'alpinejs';

const headers = () => ({
	Accept: 'application/json',
	'Content-Type': 'application/json',
	'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

export default {
	/** The booking whose cancellation is being confirmed, or null. */
	cancelling: null,

	/** The booking whose laptop is being given up, or null. */
	cancellingRental: null,

	busy: false,

	askCancel(booking) {
		this.cancelling = booking;
	},

	askCancelRental(booking) {
		this.cancellingRental = booking;
	},

	/**
	 * The sentence the confirmation shows.
	 *
	 * Legacy's two messages, verbatim. The penalty one is assembled from the
	 * server's figures rather than from anything worked out here — `amount` is
	 * francs and `rate` is the percentage, both already decided.
	 */
	get cancelMessage() {
		if (!this.cancelling) return '';

		if (!this.cancelling.penalty) {
			return 'Bitte Annullation bestätigen. Die Annullation wird Dir per E-Mail bestätigt.';
		}

		return (
			`Die kurzfristige Annullation hat gemäss unseren AGB Kosten zur Folge. ` +
			`Diese belaufen sich auf CHF ${this.cancelling.amount} ` +
			`(${this.cancelling.rate}% der Kurskosten, abzüglich allfällige Rabatte).`
		);
	},

	async confirmCancel() {
		if (this.busy || !this.cancelling) return;

		const { uuid } = this.cancelling;

		await this.send(`/api/bookings/${uuid}/cancel`, {}, 'Die Buchung wurde annulliert.');

		this.cancelling = null;
	},

	async confirmCancelRental() {
		if (this.busy || !this.cancellingRental) return;

		const { uuid } = this.cancellingRental;

		await this.send(`/api/bookings/${uuid}/rental`, { rental: false }, 'Der Mietcomputer wurde storniert.');

		this.cancellingRental = null;
	},

	/** No confirmation: legacy adds it on the click, and it can be dropped again. */
	async addRental(uuid) {
		await this.send(`/api/bookings/${uuid}/rental`, { rental: true }, 'Der Mietcomputer wurde gebucht.');
	},

	/**
	 * Every one of the three changes what the page says — a row moves, a line
	 * appears, a price changes — so the page is reloaded rather than patched.
	 *
	 * That is legacy's own behaviour (`this.find()` refetches the whole
	 * profile), and here it costs one render of a page the server built in the
	 * first place. Patching four lists in the browser would mean a second
	 * rendering of the same rows in JavaScript, which is the thing this site is
	 * built not to have.
	 *
	 * The toast is carried across the reload in `sessionStorage`, because the
	 * message belongs to the action and the action is finished before the new
	 * page exists.
	 */
	async send(url, body, message) {
		this.busy = true;

		try {
			const response = await fetch(url, {
				method: 'PATCH',
				headers: headers(),
				body: JSON.stringify(body),
			});

			if (!response.ok) {
				this.busy = false;
				Alpine.store('toast').show('Es ist ein Fehler aufgetreten.', 'error');

				return;
			}

			sessionStorage.setItem('portal.toast', message);
			window.location.reload();
		} catch {
			this.busy = false;
			Alpine.store('toast').show('Es ist ein Fehler aufgetreten.', 'error');
		}
	},

	/** Picks up the message the reload was started for, once. */
	init() {
		const message = sessionStorage.getItem('portal.toast');

		if (message) {
			sessionStorage.removeItem('portal.toast');
			queueMicrotask(() => Alpine.store('toast').show(message, 'info'));
		}
	},
};
