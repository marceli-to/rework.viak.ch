@props(['order'])

@php
	/*
	 * Payment and dispatch as badges, in the words the dashboard's order list
	 * and customer page use ([[viak-portals-mirror-dashboard]]).
	 */
	$payment = match (true) {
		$order->isCancelled() => ['Storniert', 'neutral'],
		$order->invoice === null => ['Kostenlos', 'neutral'],
		$order->isPaid() => ['Bezahlt', 'success'],
		$order->invoice->isCancelled() => ['Storniert', 'neutral'],
		default => ['Rechnung offen', 'warning'],
	};
	$sent = $order->items->every(fn ($item) => $item->dispatched_at !== null);
@endphp

{{--
	One licence order in the profile's *Bestellungen* ([[13-checkout]]): the
	document row's frame and its 4 / 3 / 5 (`row/document`). The order and its
	date, what was ordered, then the amount with its badges and, while the
	invoice is open, *Bezahlen* to its card payment (#45).
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<div class="sm:col-span-4">
			<strong class="font-bold">Bestellung {{ $order->number }}</strong><br>
			{{ $order->created_at->format('d.m.Y') }}
		</div>

		<div class="sm:col-span-3">
			@foreach ($order->items as $item)
				{{ $item->description() }}@if (! $loop->last)<br>@endif
			@endforeach
		</div>

		<div class="flex justify-between gap-16 max-sm:mt-16 sm:col-span-5">
			{{-- The amount, then the badges on a line of their own, 8px apart
			     across and down, as the event row draws its state
			     (`row/event`, Marcel, 2026-10-20). --}}
			<div>
				@if ($order->invoice)
					<div>CHF {{ number_format((float) $order->invoice->grand_total, 2, '.', '') }}</div>
				@endif
				<div @class(['flex flex-wrap items-start gap-8', 'mt-4' => $order->invoice])>
					<x-ui.badge :variant="$payment[1]">{{ $payment[0] }}</x-ui.badge>
					@unless ($order->isCancelled())
						<x-ui.badge :variant="$sent ? 'success' : 'neutral'">{{ $sent ? 'Versendet' : 'Nicht versendet' }}</x-ui.badge>
					@endunless
				</div>
			</div>

			@if ($order->invoice?->isPending())
				<div>
					<x-ui.button href="{{ \App\Support\SiteUrl::invoicePayment($order->invoice->uuid) }}">Bezahlen</x-ui.button>
				</div>
			@endif
		</div>
	</div>
</article>
