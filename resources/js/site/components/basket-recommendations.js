/**
 * *Das könnte dich auch interessieren* under the basket ([[Recommendations]]):
 * asked of the server for what is in the basket, again whenever that changes.
 * The cards come back as HTML, because they are the site's Blade cards and
 * this page is drawn in the browser.
 */
export default () => ({
	html: '',

	init() {
		this.load();
		this.$watch(() => JSON.stringify([this.$store.basket.items, this.$store.basket.licences]), () => this.load());
	},

	async load() {
		const { items, licences } = this.$store.basket;

		if (!items.length && !licences.length) {
			this.html = '';
			return;
		}

		try {
			const response = await fetch('/api/basket/recommendations', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Accept: 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
				body: JSON.stringify({ items, licences }),
			});

			this.html = response.ok ? (await response.json()).html : '';
		} catch {
			// Nothing to recommend is a fine outcome; the basket works without it.
			this.html = '';
		}
	},
});
