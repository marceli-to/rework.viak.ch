import { reactive } from 'vue';

/**
 * Ask before something that cannot be taken back, and wait for the answer
 * ([[07-dashboard]]):
 *
 *   if (await confirm('Bitte Löschen bestätigen!', 'Der Kurs wird gelöscht.')) …
 *
 * `choices` puts more than one way forward above *Abbrechen*, each answering
 * with its own value — *Annullieren* asks whether the cost is charged (#14):
 *
 *   await confirm('Bitte Annullation bestätigen!', text, { choices: [{ label: 'Mit Kosten', value: 'charge' }, …] })
 *
 * *Abbrechen*, Escape and the veil always answer `false`.
 *
 * `<ConfirmDialog>` in the shell draws it — the site's own confirm dialog. One
 * question at a time; asking again answers the open one with no.
 */
const CONFIRM = [{ label: 'Bestätigen', value: true }];

export const confirmState = reactive({ open: false, message: '', text: '', choices: CONFIRM, resolve: null });

export function confirm(message = 'Bitte Löschen bestätigen!', text = '', { choices = CONFIRM } = {}) {
	confirmState.resolve?.(false);

	return new Promise((resolve) => Object.assign(confirmState, { open: true, message, text, choices, resolve }));
}

export function answer(value) {
	confirmState.resolve?.(value);
	Object.assign(confirmState, { open: false, resolve: null });
}
