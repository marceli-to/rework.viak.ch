/**
 * The message that drops into the top right ([[09-public-site]]).
 *
 * Legacy has **two** toasts that look the same and are not the same thing: a
 * Blade partial (`web/components/notification.blade.php`, `.notification
 * is-toast`) for a server-side flash, and `vue-toast-notification` for anything
 * the browser decides — a course removed from the basket, a bookmark saved.
 * `vendor/vue-toast/_main.scss` exists only to make the second look like the
 * first, and it very nearly does: measured on production, both are white bold
 * text on a solid bar, 360px wide, anchored to the container's right edge.
 *
 * Here they are one component, `<x-ui.toast>`, in two modes — the flash on
 * the page renders it with a slot, and this store drives the live one.
 *
 * **Grey is the default, not an oversight.** `$toast-colors` maps `default` to
 * `#505050`; legacy calls `$toast.open('…')` with no type for both basket
 * messages, so the bar is grey rather than green. Reserving the colours for
 * *outcomes* the customer has to notice is the reason the removal reads as
 * neutral.
 */
export default {
	message: null,
	variant: 'info',
	open: false,
	timer: null,

	/**
	 * `vue-toast-notification`'s own default duration, which
	 * `shared/bootstrap.js` overrides only the position of. Legacy's *modal*
	 * autohide is 2000ms and is a different control — no live toast uses it.
	 */
	duration: 6000,

	show(message, variant = 'info') {
		this.message = message;
		this.variant = variant;
		this.open = true;

		clearTimeout(this.timer);
		this.timer = setTimeout(() => this.hide(), this.duration);
	},

	hide() {
		clearTimeout(this.timer);
		this.open = false;
	},
};
