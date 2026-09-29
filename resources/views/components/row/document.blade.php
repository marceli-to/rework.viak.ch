@props(['document'])

@php
	$locale = app()->getLocale();
	$invoice = $document->invoice();
	$event = $document->relatedBooking()?->event;

	/*
	 * The invoice's state as a badge beside the amount, with the words and
	 * tones the dashboard's student page uses (Marcel, 2026-09-29: the portals
	 * look like the admin's screens). Legacy's `StackedListDocument` printed an
	 * italic `(bezahlt)` / `(offen)` / `(fällig)` and nothing for a cancelled
	 * invoice; the dashboard says *Storniert*, so this does too.
	 */
	$status = match ($invoice?->status) {
		\App\Enums\InvoiceStatus::Paid => ['Bezahlt', 'success'],
		\App\Enums\InvoiceStatus::Open => ['Offen', 'neutral'],
		\App\Enums\InvoiceStatus::Overdue => ['Fällig', 'danger'],
		\App\Enums\InvoiceStatus::Cancelled => ['Storniert', 'neutral'],
		default => null,
	};
@endphp

{{--
	One row of *Meine Dokumente* — `StackedListDocument.vue`.

	`.stacked-list-item` is the same `%stacked-list` geometry as the event row,
	split **4 / 3 / 5** instead of three quarters: the course it belongs to,
	what the document is, then the money and the button. Measured against the
	live stylesheet on 2026-09-22 — 329 / 237 / 422 of 1068 at desktop, mt 32,
	pt 16, a 1px black rule, 18px on 25.2.

	The last column stays a `justify-between` flex **at every width**, which is
	why *Download* sits beside the amount on a phone rather than spanning the
	column the way the event row's buttons do.
--}}
<article class="relative mt-16 border-t border-black pt-8 leading-[1.5] sm:mt-32 sm:pt-16 sm:text-lg sm:leading-[1.4] lg:text-xl">
	<div class="sm:grid sm:grid-cols-12 sm:gap-16 lg:gap-40">
		<div class="sm:col-span-4">
			@if ($event)
				<strong class="font-bold">{{ $event->course->getTranslation('title', $locale) }}</strong><br>
				{{ $event->date->format('d.m.Y') }}
			@else
				{{-- The port kept documents whose booking or invoice did not
				     survive rather than dropping them: the PDF is still the
				     customer's ([[PortDocuments]]). None in the current data,
				     but the row has to render when there is one. --}}
				<strong class="font-bold">{{ $document->type->label() }}</strong><br>
				{{ $document->date?->format('d.m.Y') }}
			@endif
		</div>

		<div class="sm:col-span-3">
			{{ $document->type->label() }}@if ($invoice?->number) {{ $invoice->number }}@endif
		</div>

		<div class="flex justify-between sm:col-span-5">
			<div>
				@if ($invoice)
					CHF {{ number_format((float) $invoice->grand_total, 2, '.', '') }}
				@endif
				@if ($status)
					<x-ui.badge :variant="$status[1]" class="ml-8 align-middle">{{ $status[0] }}</x-ui.badge>
				@endif
			</div>

			<div>
				{{-- **A policy-gated route, not a public path.** Legacy stored
				     a URL under the `public/storage` symlink, so every invoice
				     and every participation confirmation on the live site is
				     fetchable without authenticating — 1,162 of them, behind
				     nothing but the uuid in the path ([[08-accounts]], finding
				     3). This one asks [[UserDocumentPolicy]].

				     `target="_blank"` is legacy's, and it is right here: the
				     list is something a customer works through. --}}
				<x-ui.button href="{{ route('documents.show', $document->uuid) }}"
					target="_blank"
					title="{{ $document->type->label() }}">Download</x-ui.button>
			</div>
		</div>
	</div>
</article>
