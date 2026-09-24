import { reactive } from 'vue';

/**
 * Ask before something that cannot be taken back, and wait for the answer
 * ([[07-dashboard]]):
 *
 *   if (await confirm('Bitte Löschen bestätigen!', 'Der Kurs wird gelöscht.')) …
 *
 * `<ConfirmDialog>` in the shell draws it — the site's own confirm dialog. One
 * question at a time; asking again answers the open one with no.
 */
export const confirmState = reactive({ open: false, message: '', text: '', resolve: null });

export function confirm(message = 'Bitte Löschen bestätigen!', text = '') {
	confirmState.resolve?.(false);

	return new Promise((resolve) => Object.assign(confirmState, { open: true, message, text, resolve }));
}

export function answer(value) {
	confirmState.resolve?.(value);
	Object.assign(confirmState, { open: false, resolve: null });
}
