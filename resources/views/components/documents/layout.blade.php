@props(['title'])

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

	Here the blocks are in flow and the only fixed measurement is the one that
	has to be fixed: the address sits where a window envelope shows it. Below
	that everything stacks, so a long address pushes the title down instead of
	being written over.
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
			   address over the gap the next block needs. Prose gets its own
			   below, because 1 is unreadable over more than a line. */
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

		.letterhead { width: 168mm; margin-bottom: 18mm; }
		.letterhead img { width: 100%; height: auto; display: block; }

		/*
			The window-envelope position, and the one measurement that stays
			absolute in spirit: 42mm from the top of the page to the first line
			of the address. Expressed as a reserved height on the block above
			rather than as `position: absolute`, so what follows is pushed down
			by a long address instead of being overlapped by it.
		*/
		.addresses { margin-bottom: 14mm; }
		.address { margin-bottom: 8mm; }
		.address:last-child { margin-bottom: 0; }
		.address__label { margin-bottom: 1.5mm; }

		.title { font-size: 16pt; font-weight: 700; line-height: 1.1; margin: 0 0 6mm 0; }
		.date { font-weight: 700; margin-bottom: 12mm; }

		table { border-collapse: collapse; border-spacing: 0; width: 100%; }
		td, th { padding: 0; text-align: left; vertical-align: top; }

		.items { margin-bottom: 10mm; }
		.items th { border-bottom: .3mm solid #000; padding: 0 0 2mm 0; font-weight: 400; }
		.items td { border-bottom: .3mm solid #000; padding: 2mm 0; }
		.items tr.total td { border-bottom: none; font-weight: 700; }
		.items .right { text-align: right; }

		/*
			Legacy's header and footer are both `position: fixed`, which in
			dompdf repeats them on **every** page — so the real invoices carry
			the full letterhead printed above the payment slip on page two,
			where the standard wants nothing at all. In flow here, so each
			appears once, on the page it belongs to.
		*/
		.footer { margin-top: 16mm; font-weight: 700; }

		.page-break { page-break-after: always; }

		/*
			The slip sits in the bottom 105mm of its own page, which is where
			the standard puts it and where legacy's is. Pushed down by a spacer
			rather than by `position: fixed; bottom: 0` — dompdf repeats a fixed
			box on every page, which is the bug above.
		*/
		.appendix { width: 210mm; }
		.appendix__spacer { height: 192mm; }

		p { margin: 0 0 4mm 0; line-height: 1.35; }
		ul { margin: 0 0 4mm 0; padding-left: 5mm; }
	</style>
	{{ $head ?? '' }}
</head>
<body>
	<div class="sheet">
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
			<div class="appendix__spacer"></div>
			{{ $appendix }}
		</div>
	@endisset
</body>
</html>
