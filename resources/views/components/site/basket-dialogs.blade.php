{{--
	The two questions the basket asks, from `frontend/event/Basket.vue`
	([[09-public-site]]).

	**One pair per page, not one per button.** Legacy renders both
	`<notification>` tags inside every `basket-button`, so a course page with
	four events carries eight hidden modals and eight copies of the same text.
	The state lives on the basket store here, which is already the one thing
	every *Buchen* talks to, so one pair serves the page.

	Include it wherever an event can be added or removed — the course page now,
	the basket page next.
--}}

{{--
	*Computer mieten*, asked **before** the add, because the rental and its price
	are frozen onto the booking ([[PriceBasket]]) and an event without
	`rentals_available` cannot sell one at all.

	Legacy's own text, including the `(Du kannst dies auch später noch
	anpassen)`. In `Basket.vue` that sits behind `\n\n`, which reads as a
	paragraph break in the source and is **not one** — `white-space: normal`
	collapses it, and production runs it on as a single sentence, which is how
	it is written here.
--}}
<x-site.modal
	wide
	message="Computer mieten"
	show="$store.basket.rentalFor"
	close="$store.basket.cancelRental()">

	<x-slot:text>
		Falls Du keinen Laptop hast, oder dieser den Anforderungen nicht genügt, kannst
		Du bei uns einen Computer mieten. Die Kosten dafür belaufen sich auf CHF 80.–
		(exkl. MwSt.) (Du kannst dies auch später noch anpassen)
	</x-slot:text>

	<x-slot:actions>
		<x-site.button variant="gray" @click="$store.basket.answerRental(true)">
			Ja gerne
		</x-site.button>
		<x-site.button variant="gray-outline" @click="$store.basket.answerRental(false)">
			Nein, ich bringe meinen eigenen Laptop
		</x-site.button>
	</x-slot:actions>
</x-site.modal>

{{--
	And the confirmation that follows, green, with the way on.

	*Warenkorb* is a link and *Schliessen* is a button, which is what they each
	are — legacy writes both as `<a href="javascript:;">`.
--}}
<x-site.modal
	variant="success"
	message="Der Kurs wurde im Warenkorb abgelegt."
	show="$store.basket.confirmed"
	close="$store.basket.confirmed = false">

	<x-slot:actions>
		<x-site.button variant="success" href="{{ \App\Support\SiteUrl::checkout('basket') }}">
			Warenkorb
		</x-site.button>
		<x-site.button variant="success-outline" @click="$store.basket.confirmed = false">
			Schliessen
		</x-site.button>
	</x-slot:actions>
</x-site.modal>
