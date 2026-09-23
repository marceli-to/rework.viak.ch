@props(['title', 'billing' => false])

{{--
	The page every generated PDF is printed on — `pdf/partials/header.blade.php`,
	`footer.blade.php` and `css/global.blade.php` rebuilt as one
	([[03-invoices]], [[08-accounts]]).

	Measured against a real ported invoice (`viak-rechnung-02-10-2024-000300.pdf`,
	in customers' hands), so the letterhead, the margins, the type and the
	footer are legacy's. What changed is how the body is laid out.

	**Legacy positions everything absolutely, in millimetres from the top**:
	`.page-info` at 42mm, `.page__title` at 85, `.page__date` at 110,
	`.page__content` padded 124 — and then a `.has-invoice-address` variant
	that moves all four down by roughly 42 because a second address block needs
	the room. Two addresses of the expected height fit exactly; a company name
	that wraps, or a fifth line, and the title lands on top of the address.
	Nothing errors, and the invoice goes out overlapping.

	Here the blocks are in flow, but each one reserves legacy's height as a
	**minimum** — so with an address of the usual length the title, the date
	and the table land on legacy's millimetre, and a long one pushes them down
	instead of being written over. `billing` is `.has-invoice-address`.

	Measured against the prod PDFs on 2026-09-23, overlaid line by line: until
	then the blocks stacked with margins of their own and every document's
	middle sat 12–43pt off legacy's, differently on each.
--}}
@php
	$assets = config('documents.assets');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<title>{{ $title }}</title>
	<style>
		/*
			Read off the filesystem, not over HTTP. Legacy loads both faces from
			`{{ url('/') }}/assets/fonts/…`, so every render makes an HTTP
			request to the application's own public URL — and a queue worker
			with no route out gets Helvetica and no warning
			([[config/documents.php]]).
		*/
		@font-face {
			font-family: 'Effra';
			font-weight: 400;
			font-style: normal;
			src: url('file://{{ $assets['fonts']['regular'] }}') format('truetype');
		}

		@font-face {
			font-family: 'Effra';
			font-weight: 700;
			font-style: normal;
			src: url('file://{{ $assets['fonts']['bold'] }}') format('truetype');
		}

		/*
			**No page margin, and the padding is on the sheet instead.** Legacy
			puts legacy's margins on `@page` — 10mm round three sides and 32 on
			the left for the punch holes — which is fine until the QR bill
			arrives: the payment slip is **210mm wide by specification**, edge to
			edge, and a page margin crops it. Measured: the creditor's name and
			the reference ran off the right of the sheet.

			A page box of zero with the padding on `.sheet` gives the content
			legacy's margins and lets the slip have the page.
		*/
		@page {
			size: A4;
			margin: 0;
		}

		body {
			color: #000;
			font-family: 'Effra', Helvetica, Arial, sans-serif;
			font-size: 10pt;
			font-weight: 400;
			/* Legacy's own, and it has to be this tight: the address blocks are
			   `<br>`-separated lines and a looser leading spreads a four-line
			   address over the gap the next block needs. Paragraphs keep it too:
			   legacy sets them at 1, and a looser leading moves the signature. */
			line-height: 1;
			margin: 0;
		}

		strong, th, h1, h2 { font-weight: 700; }

		/*
			Legacy's margins, as padding: 10mm round three sides, 32 on the left
			for the punch holes. 168 + 32 + 10 = the 210mm page.

			**Content-box, deliberately.** `box-sizing: border-box` with a 210mm
			width is the same box on paper and dompdf does not honour it: the
			content stays 210mm wide and the padding is added outside, so every
			table ran 42mm off the right edge of the page. Stating the content
			width and letting the padding add to it is the model dompdf
			implements.
		*/
		.sheet { width: 168mm; padding: 10mm 10mm 10mm 32mm; }

		/* 10 + the letterhead + 16 puts the first address line at legacy's
		   `.page-info { top: 42mm }`. */
		.letterhead { width: 168mm; margin-bottom: 16mm; }
		.letterhead img { width: 100%; height: auto; display: block; }

		/*
			Legacy's absolute tops, from the top of the page, as minimum
			heights: the address block from 42mm, the title from 85, the date
			from 110, the content from 124 — and 127 / 142 / 155 when a billing
			address stacks a second block above the first
			(`css/global.blade.php`).
		*/
		.addresses { min-height: 43mm; }
		.title { min-height: 21.2mm; }
		.date { min-height: 14mm; }
		.sheet--billing .addresses { min-height: 85mm; }
		.sheet--billing .title { min-height: 11.2mm; }
		.sheet--billing .date { min-height: 13mm; }

		/* Legacy separates its blocks with `<br><br><br>`: two blank lines at
		   Effra's 13.5pt leading. */
		.address { margin-bottom: 27pt; }
		.address:last-child { margin-bottom: 0; }
		.address__label { margin-bottom: 1.5mm; }

		/* Legacy's `<h1>` keeps dompdf's default top margin, .67em — so the
		   title's first line sits 10.7pt below the 85mm, and the reserved
		   heights above are 25 and 15mm less that margin. */
		.title { font-size: 16pt; font-weight: 700; line-height: .9; margin: .67em 0 0 0; }
		.date { font-weight: 700; }

		table { border-collapse: collapse; border-spacing: 0; width: 100%; }
		td, th { padding: 0; text-align: left; vertical-align: top; }

		.items { margin-bottom: 10mm; }
		/* `.content-table`: 1mm over, 2mm under, a .1mm rule — a 22.3pt row. */
		.items th { border-bottom: .1mm solid #000; padding: 1mm 0 2mm 0; font-weight: 400; }
		.items td { border-bottom: .1mm solid #000; padding: 1mm 0 2mm 0; }
		.items tr.total td { border-bottom: none; font-weight: 700; }
		.items .right { text-align: right; }

		/*
			**Pinned to the foot of the first page**, where legacy's
			`.footer { position: fixed; bottom: -2mm }` puts it. Absolute rather
			than fixed: dompdf repeats a fixed box on every page, and on the
			invoice's second page legacy's footer is printed across the QR
			receipt. No document runs to a second page of its own — the longest
			participant list in the data is nine rows.
		*/
		.footer { position: absolute; left: 32mm; top: 279.5mm; font-weight: 700; }

		.page-break { page-break-after: always; }

		/*
			The slip sits in the bottom 105mm of its own page, which is where
			the standard puts it and where legacy's is. Pushed down by a spacer
			rather than by `position: fixed; bottom: 0` — dompdf repeats a fixed
			box on every page, which is the bug above.
		*/
		.appendix { width: 210mm; }
		/* Legacy's letterhead repeats above the slip — its header is fixed.
		   Out of the flow, so the spacer below still measures from the top. */
		.appendix .letterhead { position: absolute; top: 10mm; left: 32mm; margin: 0; }
		/* 192mm to the cut line, less the 3.4mm "Vor der Einzahlung
		   abzutrennen" row the library prints above it. */
		.appendix__spacer { height: 188.6mm; }

		/* Legacy's own: a paragraph is a 5mm step at the body's leading. */
		p { margin: 0 0 5mm 0; }
		ul { margin: 0 0 4mm 0; padding-left: 5mm; }
	</style>
	{{ $head ?? '' }}
</head>
<body>
	<div class="sheet{{ $billing ? ' sheet--billing' : '' }}">
		{{-- An `<img>` rather than the SVG inline: dompdf renders a referenced
		     SVG and ignores an inline `<svg>` element entirely (measured, and
		     it fails silently — the logo simply is not there). --}}
		<div class="letterhead"><img src="file://{{ $assets['letterhead'] }}" alt=""></div>

		{{ $slot }}

		<div class="footer">
			<strong>visualisierungs-akademie.ch</strong><br>
			<strong>3d-software.ch</strong>
		</div>
	</div>

	{{-- Outside the sheet, so it gets the whole 210mm the standard requires. --}}
	@isset($appendix)
		<div class="page-break"></div>
		<div class="appendix">
			<div class="letterhead"><img src="file://{{ $assets['letterhead'] }}" alt=""></div>
			<div class="appendix__spacer"></div>
			{{ $appendix }}
		</div>
	@endisset
</body>
</html>
