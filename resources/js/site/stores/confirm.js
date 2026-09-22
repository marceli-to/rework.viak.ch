/**
 * *Bitte Löschen bestätigen!* — the one question the expert portal asks before
 * a form goes through ([[08-accounts]], [[09-public-site]]).
 *
 * Legacy renders a `<notification>` **inside every row**, so a course with ten
 * documents carries ten hidden dialogs and ten copies of the same sentence
 * (`shared/modules/files/components/ButtonDelete.vue`). One per page here, for
 * the reason `$store.portal` and `$store.basket` are one each.
 *
 * It holds the id of a form rather than a uuid and a route, so the dialog knows
 * nothing about what it is confirming: the page has already rendered a real
 * `<form method="POST">` with its token and its `@method('DELETE')`, and this
 * only presses the button. A link a prefetcher can fire is what the axios
 * version amounts to.
 */
export default {
	form: null,

	ask(form) {
		this.form = form;
	},

	dismiss() {
		this.form = null;
	},

	submit() {
		document.getElementById(this.form)?.submit();
		this.form = null;
	},
};
