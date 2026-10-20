@props(['removable' => false])

{{--
	One licence line of a priced basket, drawn in the browser
	([[13-checkout]]): the course row's frame (`row/basket`), its rule,
	spacing and three columns, so courses and licences read as one list.

	What, and for which host | how many, *X Stk.* | the line's net, bare and
	right-aligned as the course fees are, then *Entfernen*. The VAT is in the
	totals, as the software page's *Alle Preise exkl. MWST* promises.

	Expects `line` in scope, a `BasketResource` licence.
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<div class="sm:col-span-4">
			<h2 class="font-bold" x-text="line.product"></h2>
			<div x-text="line.label"></div>
			<div x-show="line.host" x-text="`für ${line.host}`"></div>
		</div>

		<div class="sm:col-span-4">
			<span x-text="`${line.quantity} Stk.`"></span>
		</div>

		<div class="sm:col-span-4 sm:flex sm:items-start sm:justify-between">
			<div @class(['grow sm:text-right', 'sm:mr-32 lg:mr-48' => $removable])>
				<span x-show="Number(line.net) === 0">kostenlos</span>
				<span x-show="Number(line.net) > 0" x-text="line.net"></span>
			</div>

			@if ($removable)
				<div class="mt-24 sm:mt-0">
					<x-ui.button variant="secondary"
						@click="$store.basket.removeLicence(line.variant, line.host)">Entfernen</x-ui.button>
				</div>
			@endif
		</div>
	</div>
</article>
