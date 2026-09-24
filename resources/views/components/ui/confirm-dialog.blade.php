@props(['message' => 'Bitte Löschen bestätigen!'])

{{--
	The confirmation the expert portal asks before removing a course document —
	`shared/modules/files/components/ButtonDelete.vue` ([[08-accounts]]).

	`notification.init({ message: 'Bitte Löschen bestätigen!', type: 'dialog',
	style: 'info' })`, which is the grey modal with *Bestätigen* and *Abbrechen*
	— the same pair the portal's cancellations use, and the same four-selectors-
	deep colours that make those two buttons grey inside a notification and leave
	them without a hover ([[x-ui.button]]).

	**One per page, and it names a form rather than a row.** Include it once on a
	screen where something can be deleted, and give each row a hidden
	`<form method="POST">` whose id the button hands to `$store.confirm.ask()`.
	Legacy renders one of these inside every row instead, so a course with ten
	documents carries ten copies of it ([[confirm]]).
--}}
<x-ui.modal
	:message="$message"
	show="$store.confirm.form"
	close="$store.confirm.dismiss()">

	<x-slot:actions>
		<x-ui.button variant="gray" @click="$store.confirm.submit()">Bestätigen</x-ui.button>
		<x-ui.button variant="gray-outline" @click="$store.confirm.dismiss()">Abbrechen</x-ui.button>
	</x-slot:actions>
</x-ui.modal>
