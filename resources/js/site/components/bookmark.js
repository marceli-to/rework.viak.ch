import Alpine from 'alpinejs';

/**
 * The heart on an event row — legacy's `Bookmark.vue` ([[06-bookings]]).
 *
 * Only ever rendered for a signed-in visitor; a guest gets a link to `/login`
 * in its place, because the endpoints are behind `auth:sanctum` and legacy's
 * answer to a guest clicking it is the same sentence in a dialog.
 *
 * Optimistic: the heart fills on click and rolls back if the request fails.
 * 17 bookmarks exist in three years, so a spinner would be ceremony — but a
 * heart that silently lies would not.
 *
 * ## Two behaviours, one component
 *
 * `hideAfter` is legacy's own prop, and it is what the **Merkliste** passes:
 * un-hearting a row on the list *of* hearted courses has to take the row with
 * it, or the list goes on showing a course that is no longer on it. Legacy
 * removes the element outright (`el.remove()`); this hides it, so the row can
 * come back if the request fails — which legacy's cannot, having already thrown
 * the markup away.
 *
 * Everywhere else — the course page — the row stays and only the heart changes,
 * because there the list is *courses*, not *bookmarks*.
 */
export default ({ event, saved = false, hideAfter = false }) => ({
	saved,
	busy: false,
	removed: false,

	async toggle() {
		if (this.busy) return;

		const wanted = !this.saved;

		this.busy = true;
		this.saved = wanted;

		// Hidden before the request answers, like the heart is filled before
		// it — and put back below if it fails.
		if (hideAfter && !wanted) this.removed = true;

		try {
			const response = await fetch(`/api/bookmarks/${event}`, {
				method: wanted ? 'PUT' : 'DELETE',
				headers: {
					Accept: 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
			});

			if (!response.ok) {
				this.saved = !wanted;
				this.removed = false;

				return;
			}

			/*
			 * Legacy toasts on both verbs, in these words. They were left out
			 * when the heart was built because nothing drew a toast yet; the
			 * store arrived with the basket.
			 *
			 * Grey rather than green — `$toast.open()` with no type, which is
			 * what legacy calls here ([[toast]]).
			 */
			Alpine.store('toast').show(
				wanted
					? 'Der Kurs wurde in der Merkliste gespeichert.'
					: 'Der Kurs wurde aus der Merkliste gelöscht.'
			);
		} catch {
			this.saved = !wanted;
			this.removed = false;
		} finally {
			this.busy = false;
		}
	},
});
