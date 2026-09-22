@php
	$billing = $invoice->billingLines();
	$recipient = $invoice->user?->addressLines() ?? [];

	/*
		The VAT line, or lines.
		**Legacy hardcodes the rate into the label** — `Mehrwertsteuer (0%)` on
		the course invoice and `Mehrwertsteuer 8.1%` on the rental one — which
		worked only because it had two templates for two documents that could
		never hold each other's lines. Here one invoice carries both: a course
		is exempt and the laptop beside it is not, so the rate is read off the
		lines and a mixed invoice prints one row per rate ([[InvoiceItem]]).
	*/
	$vatRows = $invoice->items
		->groupBy(fn ($item) => (string) $item->vat_rate)
		->map(fn ($items, $rate) => [
			'rate' => rtrim(rtrim(number_format((float) $rate, 1, '.', ''), '0'), '.'),
			'amount' => $items->sum(fn ($item) => (float) $item->vat),
		])
		->values();

	$money = fn ($value) => number_format((float) $value, 2, '.', "'");

	/*
		The booking numbers this invoice covers — legacy's `Buchung {number}`,
		which it prints because every invoice had exactly one booking. This one
		may cover two courses confirmed on the same day, so it is the distinct
		set off the lines rather than a single field on the header.
	*/
	$bookings = $invoice->items
		->map(fn ($item) => $item->itemable instanceof \App\Models\Booking ? $item->itemable->number : null)
		->filter()
		->unique()
		->values();
@endphp

<x-documents.layout :title="'Rechnung '.$invoice->number">
	{{--
		The invoice — `pdf/invoice/event-invoice.blade.php` and its twin
		`rental-invoice.blade.php`, which are the same file with a different
		VAT label and a different title ([[03-invoices]]).

		**One document where legacy has two.** A laptop rental could not sit on
		the same invoice as the course it belonged to, so a student who rented
		one got two invoice numbers and two QR bills for one booking. The rework
		bills the lines that became due together, whatever they are
		([[Invoice]]).
	--}}
	<div class="addresses">
		@if ($billing)
			<div class="address">
				<div class="address__label">Rechnungsadresse</div>
				@foreach ($billing as $line)
					{{ $line }}@if (! $loop->last)<br>@endif
				@endforeach
			</div>

			{{-- Only worth naming the participant when somebody else is paying. --}}
			<div class="address">
				<div class="address__label">Kursteilnehmer:in</div>
				@foreach ($recipient as $line)
					{{ $line }}@if (! $loop->last)<br>@endif
				@endforeach
			</div>
		@else
			<div class="address">
				@foreach ($recipient as $line)
					{{ $line }}@if (! $loop->last)<br>@endif
				@endforeach
			</div>
		@endif

		<div class="address">
			@if ($bookings->isNotEmpty())
				{{ $bookings->count() === 1 ? 'Buchung' : 'Buchungen' }} {{ $bookings->implode(', ') }}<br>
			@endif
			@if ($invoice->due_at)
				Zahlbar bis {{ $invoice->due_at->format('d.m.Y') }}
			@endif
		</div>
	</div>

	<h1 class="title">Rechnung {{ $invoice->number }}</h1>

	<div class="date">{{ config('documents.place') }}, {{ $invoice->date->format('d.m.Y') }}</div>

	<table class="items">
		<thead>
			<tr>
				<th style="width: 84mm">Leistung</th>
				<th style="width: 34mm">Referenz</th>
				<th style="width: 50mm" class="right">Preis</th>
			</tr>
		</thead>
		<tbody>
			{{-- `description` is frozen at issue and is the record of what was
			     billed — a course renamed in 2027 may not retitle an invoice
			     from 2024 ([[InvoiceItem]]). --}}
			@foreach ($invoice->items as $item)
				<tr>
					<td><strong>{{ $item->description }}</strong></td>
					<td>{{ $item->reference }}</td>
					<td class="right"><strong>CHF {{ $money($item->net) }}</strong></td>
				</tr>
			@endforeach

			@if ((float) $invoice->discount > 0)
				<tr>
					<td colspan="2">Abzüglich Rabatt</td>
					<td class="right">CHF −{{ $money($invoice->discount) }}</td>
				</tr>
			@endif

			@foreach ($vatRows as $vat)
				<tr>
					<td colspan="2">Mehrwertsteuer ({{ $vat['rate'] }}%)</td>
					<td class="right">CHF {{ $money($vat['amount']) }}</td>
				</tr>
			@endforeach

			<tr class="total">
				<td colspan="2">Total</td>
				<td class="right">CHF {{ $money($invoice->grand_total) }}</td>
			</tr>
		</tbody>
	</table>

	{{--
		The payment slip, in the bottom 105mm of a page of its own — where the
		standard puts it and where legacy's is.

		Everything inside is the library's, spec-conformant and validated
		([[QrBill]]), where legacy lays it out by hand in 251 lines of Blade.
	--}}
	<x-slot:appendix>{!! $qrBill !!}</x-slot:appendix>
</x-documents.layout>
