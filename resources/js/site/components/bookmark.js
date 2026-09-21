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
 */
export default ({ event, saved = false }) => ({
	saved,
	busy: false,

	async toggle() {
		if (this.busy) return;

		const wanted = !this.saved;

		this.busy = true;
		this.saved = wanted;

		try {
			const response = await fetch(`/api/bookmarks/${event}`, {
				method: wanted ? 'PUT' : 'DELETE',
				headers: {
					Accept: 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
				},
			});

			if (!response.ok) this.saved = !wanted;
		} catch {
			this.saved = !wanted;
		} finally {
			this.busy = false;
		}
	},
});
