import { defineStore } from 'pinia';
import { fetchEvents, setEventState } from '@/api/events';

export const useEventStore = defineStore('events', {
	state: () => ({
		items: [],
		loading: false,
		error: null,
		showPast: false,
	}),

	actions: {
		async load() {
			this.loading = true;
			this.error = null;

			try {
				this.items = await fetchEvents({ past: this.showPast ? 1 : 0 });
			} catch (error) {
				this.error = error.message;
			} finally {
				this.loading = false;
			}
		},

		async togglePast() {
			this.showPast = !this.showPast;
			await this.load();
		},

		/**
		 * Replaces the row in place rather than reloading the list — the server
		 * response is the authority on the new state and its timestamp.
		 */
		async changeState(uuid, state) {
			this.error = null;

			try {
				const updated = await setEventState(uuid, state);
				const index = this.items.findIndex((event) => event.uuid === uuid);

				if (index !== -1) {
					this.items.splice(index, 1, updated);
				}
			} catch (error) {
				this.error = error.message;
			}
		},
	},
});
