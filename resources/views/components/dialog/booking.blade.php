{{--
	The two confirmations the portal asks before changing a booking, from
	`shared/mixins/Booking.js` ([[08-accounts]], [[09-public-site]]).

	**One pair per page**, on the store, for the reason `x-dialog.basket`
	is: legacy renders a `<notification>` inside every row, so a portal with six
	booked courses carries twelve hidden dialogs and twelve copies of the same
	text.

	Include it on any screen where a booking can be cancelled — the profile and
	the booked-event detail.
--}}

{{--
	*Annullieren*, and **the price of doing it now**.

	Legacy shows two different sentences here and the difference is money: inside
	the penalty window it names the amount and the rate before asking, otherwise
	it just asks. Both are in `$store.portal.cancelMessage`, assembled from
	figures the **server** rendered into the row — [[CancellationPenalty]], the
	same class that raises the invoice, so the dialog cannot promise one number
	and the bill say another.

	`06-bookings.md` notes that the response carries the penalty invoice because
	the student has to be told at the moment they cancel rather than when the
	bill arrives. This is the other half of that: told *before* they cancel.
--}}
<x-ui.modal
	wide
	x-bind:data-booking="$store.portal.cancelling?.uuid"
	show="$store.portal.cancelling"
	close="$store.portal.cancelling = null">

	<x-slot:text>
		<span x-text="$store.portal.cancelMessage"></span>
	</x-slot:text>

	<x-slot:actions>
		<x-ui.button variant="gray"
			x-bind:disabled="$store.portal.busy"
			@click="$store.portal.confirmCancel()">Bestätigen</x-ui.button>
		<x-ui.button variant="gray-outline"
			@click="$store.portal.cancelling = null">Abbrechen</x-ui.button>
	</x-slot:actions>
</x-ui.modal>

{{-- Giving the laptop back. No penalty applies to a rental, so there is one
     sentence and legacy asks it plainly. --}}
<x-ui.modal
	show="$store.portal.cancellingRental"
	close="$store.portal.cancellingRental = null"
	message="Möchtest Du den Mietcomputer wirklich stornieren?">

	<x-slot:actions>
		<x-ui.button variant="gray"
			x-bind:disabled="$store.portal.busy"
			@click="$store.portal.confirmCancelRental()">Bestätigen</x-ui.button>
		<x-ui.button variant="gray-outline"
			@click="$store.portal.cancellingRental = null">Abbrechen</x-ui.button>
	</x-slot:actions>
</x-ui.modal>

{{-- The store shows a toast after the reload the three actions end in; `init()`
     is what picks it up, and it needs an element in the page to run on. --}}
<div x-data x-init="$store.portal.init()"></div>
