/** The burger menu. Legacy did this with a jQuery-era class toggle. */
export default () => ({
	open: false,

	toggle() {
		this.open = !this.open;
		// Stops the page behind a full-screen menu from scrolling under it.
		document.body.classList.toggle('overflow-hidden', this.open);
	},

	close() {
		this.open = false;
		document.body.classList.remove('overflow-hidden');
	},
});
