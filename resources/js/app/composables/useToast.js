import { reactive } from 'vue';

/**
 * Short confirmations after an action — *Reihenfolge angepasst* — drawn by
 * `<Toast>` in the shell ([[07-dashboard]]). Each one goes by itself after four
 * seconds, as the site's does.
 */
export const toasts = reactive([]);

let id = 0;

export function toast(title, tone = 'success') {
	const item = { id: ++id, title, tone };
	toasts.push(item);
	setTimeout(() => dismiss(item.id), 4000);
}

export function dismiss(toastId) {
	const index = toasts.findIndex((item) => item.id === toastId);
	if (index !== -1) toasts.splice(index, 1);
}
